<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\StoresRestApi\Processor\Mapper;

use Generated\Shared\Transfer\DynamicStoreRestAttributesTransfer;
use Generated\Shared\Transfer\StoreLocaleRestAttributesTransfer;
use Generated\Shared\Transfer\StoresRestAttributesTransfer;
use Generated\Shared\Transfer\StoreTransfer;

class StoresResourceMapper implements StoresResourceMapperInterface
{
    /**
     * @param array<\Generated\Shared\Transfer\StoreCountryRestAttributesTransfer> $countries
     * @param array<\Generated\Shared\Transfer\StoreCurrencyRestAttributesTransfer> $currencies
     *
     * @return \Generated\Shared\Transfer\StoresRestAttributesTransfer
     */
    public function mapStoreToStoresRestAttribute(
        StoreTransfer $storeTransfer,
        array $countries,
        array $currencies
    ): StoresRestAttributesTransfer {
        $storesRestAttributes = new StoresRestAttributesTransfer();

        $storesRestAttributes = $this->addLocaleToStoresRestAttributes(
            $storesRestAttributes,
            $storeTransfer->getAvailableLocaleIsoCodes(),
        );

        $storesRestAttributes = $this->addStoreCountryToStoresRestAttributes(
            $storesRestAttributes,
            $countries,
        );

        $storesRestAttributes = $this->addTimeZoneToStoresRestAttributes($storesRestAttributes, $storeTransfer->getTimezone());

        $storesRestAttributes = $this->addDefaultCurrencyToStoresRestAttributes(
            $storesRestAttributes,
            $storeTransfer->getDefaultCurrencyIsoCodeOrFail(),
        );

        $storesRestAttributes = $this->addStoreCurrencyToStoresRestAttributes(
            $storesRestAttributes,
            $currencies,
        );

        return $storesRestAttributes;
    }

    public function mapStoresRestAttributesTransferToDynamicStoreRestAttributesTransfer(
        StoresRestAttributesTransfer $storesRestAttributesTransfer
    ): DynamicStoreRestAttributesTransfer {
        return (new DynamicStoreRestAttributesTransfer())->fromArray($storesRestAttributesTransfer->toArray(), true);
    }

    protected function addLocaleToStoresRestAttributes(
        StoresRestAttributesTransfer $storesRestAttributes,
        array $locales
    ): StoresRestAttributesTransfer {
        foreach ($locales as $identifier => $name) {
            $storesLocaleAttributes = (new StoreLocaleRestAttributesTransfer())
                ->setName($name)
                ->setCode($identifier);

            $storesRestAttributes->addLocale($storesLocaleAttributes);
        }

        return $storesRestAttributes;
    }

    protected function addTimeZoneToStoresRestAttributes(
        StoresRestAttributesTransfer $storesRestAttributes,
        ?string $timeZone
    ): StoresRestAttributesTransfer {
        return $storesRestAttributes->setTimeZone($timeZone);
    }

    protected function addDefaultCurrencyToStoresRestAttributes(
        StoresRestAttributesTransfer $storesRestAttributes,
        string $defaultCurrency
    ): StoresRestAttributesTransfer {
        $storesRestAttributes->setDefaultCurrency($defaultCurrency);

        return $storesRestAttributes;
    }

    /**
     * @param \Generated\Shared\Transfer\StoresRestAttributesTransfer $storesRestAttributes
     * @param array<\Generated\Shared\Transfer\StoreCountryRestAttributesTransfer> $storeCountryAttributes
     *
     * @return \Generated\Shared\Transfer\StoresRestAttributesTransfer
     */
    protected function addStoreCountryToStoresRestAttributes(
        StoresRestAttributesTransfer $storesRestAttributes,
        array $storeCountryAttributes
    ): StoresRestAttributesTransfer {
        foreach ($storeCountryAttributes as $storeCountryAttribute) {
            $storesRestAttributes->addCountry($storeCountryAttribute);
        }

        return $storesRestAttributes;
    }

    /**
     * @param \Generated\Shared\Transfer\StoresRestAttributesTransfer $storesRestAttributes
     * @param array<\Generated\Shared\Transfer\StoreCurrencyRestAttributesTransfer> $storeCurrencyAttributes
     *
     * @return \Generated\Shared\Transfer\StoresRestAttributesTransfer
     */
    protected function addStoreCurrencyToStoresRestAttributes(
        StoresRestAttributesTransfer $storesRestAttributes,
        array $storeCurrencyAttributes
    ): StoresRestAttributesTransfer {
        foreach ($storeCurrencyAttributes as $storeCurrencyAttribute) {
            $storesRestAttributes->addCurrency($storeCurrencyAttribute);
        }

        return $storesRestAttributes;
    }
}
