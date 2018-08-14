<?php
/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\StoresRestApi\Processor\Mapper;

use Generated\Shared\Transfer\CurrencyTransfer;
use Generated\Shared\Transfer\StoreCurrencyRestAttributesTransfer;
use Spryker\Glue\StoresRestApi\StoresRestApiConfig;
use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceBuilderInterface;
use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceInterface;
use Spryker\Shared\Kernel\Store;

class StoresCurrencyResourceMapper implements StoresCurrencyResourceMapperInterface
{
    /**
     * @param CurrencyTransfer $currencyTransfer
     * @param Store $store
     *
     * @return \Generated\Shared\Transfer\StoreCountryRestAttributesTransfer
     */
    public function mapCurrencyToStoresCurrencyRestAttributes(CurrencyTransfer $currencyTransfer, Store $store): StoreCurrencyRestAttributesTransfer
    {
        $storesCurrencyAttributes = (new StoreCurrencyRestAttributesTransfer())
            ->setCurrencyIsoCode($currencyTransfer->getCode())
            ->setName($currencyTransfer->getName())
            ->setCurrencyIsoCodes($store->getCurrencyIsoCodes())
            ->setDefaultCurrency($store->getDefaultCurrencyCode());

        return $storesCurrencyAttributes;
    }
}
