<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\StoresRestApi\Processor\Stores;

use Generated\Shared\Transfer\RestErrorMessageTransfer;
use Generated\Shared\Transfer\StoreConditionsTransfer;
use Generated\Shared\Transfer\StoreCriteriaTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Spryker\Client\StoreStorage\StoreStorageClientInterface;
use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceBuilderInterface;
use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResponseInterface;
use Spryker\Glue\GlueApplication\Rest\Request\Data\RestRequestInterface;
use Spryker\Glue\StoresRestApi\Dependency\Client\StoresRestApiToStoreClientInterface;
use Spryker\Glue\StoresRestApi\Processor\Mapper\StoresResourceMapperInterface;
use Spryker\Glue\StoresRestApi\StoresRestApiConfig;
use Symfony\Component\HttpFoundation\Response;

class StoresReader implements StoresReaderInterface
{
    /**
     * @var \Spryker\Glue\StoresRestApi\Processor\Stores\StoresCountryReaderInterface
     */
    protected $countryReader;

    /**
     * @var \Spryker\Glue\StoresRestApi\Processor\Stores\StoresCurrencyReaderInterface
     */
    protected $currencyReader;

    /**
     * @var \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceBuilderInterface
     */
    protected $restResourceBuilder;

    /**
     * @var \Spryker\Glue\StoresRestApi\Processor\Mapper\StoresResourceMapperInterface
     */
    protected $storesResourceMapper;

    /**
     * @var \Spryker\Glue\StoresRestApi\Dependency\Client\StoresRestApiToStoreClientInterface
     */
    protected $storeClient;

    protected StoreStorageClientInterface $storeStorageClient;

    public function __construct(
        StoresCountryReaderInterface $countryReader,
        StoresCurrencyReaderInterface $currencyReader,
        RestResourceBuilderInterface $restResourceBuilder,
        StoresResourceMapperInterface $storesResourceMapper,
        StoresRestApiToStoreClientInterface $storeClient,
        StoreStorageClientInterface $storeStorageClient
    ) {
        $this->countryReader = $countryReader;
        $this->currencyReader = $currencyReader;
        $this->restResourceBuilder = $restResourceBuilder;
        $this->storesResourceMapper = $storesResourceMapper;
        $this->storeClient = $storeClient;
        $this->storeStorageClient = $storeStorageClient;
    }

    public function getStoresAttributes(RestRequestInterface $restRequest): RestResponseInterface
    {
        /* Required by infrastructure, exists only for BC with DMS OFF mode. */
        if ($this->storeClient->isDynamicStoreEnabled()) {
            return $this->getDynamicStoreStoresAttributes($restRequest);
        }

        $currentStore = $this->storeClient->getCurrentStore();
        $storeId = $restRequest->getResource()->getId();

        if ($storeId && ($storeId !== $currentStore->getName())) {
            return $this->getInvalidStoreRestResponse();
        }

        return $this->getRestResponse($currentStore, $this->restResourceBuilder->createRestResponse());
    }

    protected function getDynamicStoreStoresAttributes(RestRequestInterface $restRequest): RestResponseInterface
    {
        $response = $this->restResourceBuilder->createRestResponse();
        $storeId = $restRequest->getResource()->getId();

        if ($storeId !== null) {
            /* Storage-first existence check: `StoreClient::getStoreByName()` throws for a store no expander
               can resolve, and the legacy Glue stack would surface that as a 500 instead of the 404 below. */
            if ($this->storeStorageClient->findStoreByName($storeId) === null) {
                return $this->getInvalidStoreRestResponse();
            }

            $storeTransfer = $this->storeClient->getStoreByName($storeId);

            if ($storeTransfer->getIdStore() === null) {
                return $this->getInvalidStoreRestResponse();
            }

            return $this->getRestResponse($storeTransfer, $response);
        }

        foreach ($this->getVisibleStoreTransfers() as $storeTransfer) {
            $response = $this->getRestResponse($storeTransfer, $response);
        }

        return $response;
    }

    /**
     * Storage-first: falls back to the Gateway read only when storage has nothing published yet.
     *
     * @return array<\Generated\Shared\Transfer\StoreTransfer>
     */
    protected function getVisibleStoreTransfers(): array
    {
        $storeTransfers = $this->getVisibleStoreTransfersFromStorage();

        if ($storeTransfers !== []) {
            return $storeTransfers;
        }

        /* BC fallback: storage can be empty (cold Redis, not yet synced) — fall back to a live Zed read. */
        return $this->getVisibleStoreTransfersFromGateway();
    }

    /**
     * Reads the store_list plus per-store storage entries; excludes hidden stores by construction,
     * since the publisher never writes a storage row for a store with isVisibleToCustomer=false.
     *
     * @return array<\Generated\Shared\Transfer\StoreTransfer>
     */
    protected function getVisibleStoreTransfersFromStorage(): array
    {
        $storeTransfers = [];

        foreach ($this->storeStorageClient->getStoreNames() as $storeName) {
            $storeStorageTransfer = $this->storeStorageClient->findStoreByName($storeName);

            if ($storeStorageTransfer === null) {
                continue;
            }

            $storeTransfers[] = (new StoreTransfer())->fromArray($storeStorageTransfer->toArray(), true);
        }

        return $storeTransfers;
    }

    /**
     * BC-only fallback path for when storage is empty; goes through the live Zed Gateway instead.
     *
     * @return array<\Generated\Shared\Transfer\StoreTransfer>
     */
    protected function getVisibleStoreTransfersFromGateway(): array
    {
        $storeCriteriaTransfer = (new StoreCriteriaTransfer())
            ->setStoreConditions((new StoreConditionsTransfer())->setIsVisibleToCustomer(true));

        return $this->storeClient->getStoreCollection($storeCriteriaTransfer)->getStores()->getArrayCopy();
    }

    protected function getRestResponse(
        StoreTransfer $storeTransfer,
        RestResponseInterface $restResponse
    ): RestResponseInterface {
        $restAttributesTransfer = $this->storesResourceMapper->mapStoreToStoresRestAttribute(
            $storeTransfer,
            $this->countryReader->getStoresCountryAttributes($storeTransfer->getCountries()),
            $this->currencyReader->getStoresCurrencyAttributes($storeTransfer->getAvailableCurrencyIsoCodes()),
        );

        /* Required by infrastructure, exists only for BC with DMS OFF mode. */
        if ($this->storeClient->isDynamicStoreEnabled()) {
            $restAttributesTransfer = $this->storesResourceMapper->mapStoresRestAttributesTransferToDynamicStoreRestAttributesTransfer(
                $restAttributesTransfer,
            );
        }

        $restResource = $this->restResourceBuilder->createRestResource(
            StoresRestApiConfig::RESOURCE_STORES,
            $storeTransfer->getName(),
            $restAttributesTransfer,
        );

        return $restResponse->addResource($restResource);
    }

    protected function getInvalidStoreRestResponse(): RestResponseInterface
    {
        return $this->addInvalidStoreIdErrorToResponse($this->restResourceBuilder->createRestResponse());
    }

    protected function addInvalidStoreIdErrorToResponse(RestResponseInterface $restResponse): RestResponseInterface
    {
        $restErrorTransfer = (new RestErrorMessageTransfer())
            ->setCode(StoresRestApiConfig::RESPONSE_CODE_STORE_NOT_FOUND)
            ->setStatus(Response::HTTP_NOT_FOUND)
            ->setDetail(StoresRestApiConfig::RESPONSE_MESSAGE_STORE_NOT_FOUND);

        return $restResponse->addError($restErrorTransfer);
    }
}
