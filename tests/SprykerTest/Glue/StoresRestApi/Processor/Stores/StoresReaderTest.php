<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Glue\StoresRestApi\Processor\Stores;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\StoreCollectionTransfer;
use Generated\Shared\Transfer\StoreCriteriaTransfer;
use Generated\Shared\Transfer\StoresRestAttributesTransfer;
use Generated\Shared\Transfer\StoreStorageTransfer;
use PHPUnit\Framework\MockObject\MockObject;
use Spryker\Client\StoreStorage\StoreStorageClientInterface;
use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceBuilderInterface;
use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceInterface;
use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResponseInterface;
use Spryker\Glue\GlueApplication\Rest\Request\Data\RestRequestInterface;
use Spryker\Glue\StoresRestApi\Dependency\Client\StoresRestApiToStoreClientInterface;
use Spryker\Glue\StoresRestApi\Processor\Mapper\StoresResourceMapperInterface;
use Spryker\Glue\StoresRestApi\Processor\Stores\StoresCountryReaderInterface;
use Spryker\Glue\StoresRestApi\Processor\Stores\StoresCurrencyReaderInterface;
use Spryker\Glue\StoresRestApi\Processor\Stores\StoresReader;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Glue
 * @group StoresRestApi
 * @group Processor
 * @group Stores
 * @group StoresReaderTest
 * Add your own group annotations below this line
 */
class StoresReaderTest extends Unit
{
    /**
     * @var string
     */
    protected const STORE_NAME = 'AT';

    /**
     * The collection listing (dynamic multi-store mode, no explicit store id in the request) is
     * store_list-backed: when storage has stores, it must read from storage and MUST NOT fall back
     * to the live Gateway call.
     */
    public function testGetStoresAttributesReadsFromStorageWhenStorageHasStores(): void
    {
        // Arrange
        $storeClientMock = $this->getMockBuilder(StoresRestApiToStoreClientInterface::class)->getMock();
        $storeClientMock->method('isDynamicStoreEnabled')->willReturn(true);
        $storeClientMock->expects($this->never())->method('getStoreCollection');

        $storeStorageClientMock = $this->getMockBuilder(StoreStorageClientInterface::class)->getMock();
        $storeStorageClientMock->expects($this->once())->method('getStoreNames')->willReturn([static::STORE_NAME]);
        $storeStorageClientMock->method('findStoreByName')
            ->with(static::STORE_NAME)
            ->willReturn((new StoreStorageTransfer())->setName(static::STORE_NAME));

        $storesReader = $this->createStoresReader($storeClientMock, $storeStorageClientMock);

        // Act
        $storesReader->getStoresAttributes($this->createRestRequestMockWithoutStoreId());
    }

    /**
     * Storage can legitimately be empty (cold Redis, not yet synced) — the collection listing MUST
     * fall back to the live Gateway call, still requesting storefront-visible stores only.
     */
    public function testGetStoresAttributesFallsBackToGatewayWhenStorageIsEmpty(): void
    {
        // Arrange
        $capturedStoreCriteriaTransfer = null;

        $storeClientMock = $this->getMockBuilder(StoresRestApiToStoreClientInterface::class)->getMock();
        $storeClientMock->method('isDynamicStoreEnabled')->willReturn(true);
        $storeClientMock->expects($this->once())
            ->method('getStoreCollection')
            ->with($this->callback(function (StoreCriteriaTransfer $storeCriteriaTransfer) use (&$capturedStoreCriteriaTransfer) {
                $capturedStoreCriteriaTransfer = $storeCriteriaTransfer;

                return true;
            }))
            ->willReturn(new StoreCollectionTransfer());

        $storeStorageClientMock = $this->getMockBuilder(StoreStorageClientInterface::class)->getMock();
        $storeStorageClientMock->method('getStoreNames')->willReturn([]);

        $storesReader = $this->createStoresReader($storeClientMock, $storeStorageClientMock);

        // Act
        $storesReader->getStoresAttributes($this->createRestRequestMockWithoutStoreId());

        // Assert
        $this->assertNotNull($capturedStoreCriteriaTransfer);
        $this->assertTrue($capturedStoreCriteriaTransfer->getStoreConditionsOrFail()->getIsVisibleToCustomer());
    }

    protected function createStoresReader(
        StoresRestApiToStoreClientInterface $storeClientMock,
        StoreStorageClientInterface $storeStorageClientMock
    ): StoresReader {
        $restResponseMock = $this->getMockBuilder(RestResponseInterface::class)->getMock();
        $restResponseMock->method('addResource')->willReturn($restResponseMock);

        $restResourceBuilderMock = $this->getMockBuilder(RestResourceBuilderInterface::class)->getMock();
        $restResourceBuilderMock->method('createRestResponse')->willReturn($restResponseMock);
        $restResourceBuilderMock->method('createRestResource')
            ->willReturn($this->getMockBuilder(RestResourceInterface::class)->getMock());

        $storesResourceMapperMock = $this->getMockBuilder(StoresResourceMapperInterface::class)->getMock();
        $storesResourceMapperMock->method('mapStoreToStoresRestAttribute')
            ->willReturn(new StoresRestAttributesTransfer());

        return new StoresReader(
            $this->getMockBuilder(StoresCountryReaderInterface::class)->getMock(),
            $this->getMockBuilder(StoresCurrencyReaderInterface::class)->getMock(),
            $restResourceBuilderMock,
            $storesResourceMapperMock,
            $storeClientMock,
            $storeStorageClientMock,
        );
    }

    protected function createRestRequestMockWithoutStoreId(): RestRequestInterface&MockObject
    {
        $restResourceMock = $this->getMockBuilder(RestResourceInterface::class)->getMock();
        $restResourceMock->method('getId')->willReturn(null);

        $restRequestMock = $this->getMockBuilder(RestRequestInterface::class)->getMock();
        $restRequestMock->method('getResource')->willReturn($restResourceMock);

        return $restRequestMock;
    }
}
