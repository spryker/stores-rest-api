<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\StoresRestApi\Dependency\Client;

use Generated\Shared\Transfer\CountryCollectionTransfer;

class StoresRestApiToCountryClientBridge implements StoresRestApiToCountryClientInterface
{
    /**
     * @var \Spryker\Client\Country\CountryClientInterface
     */
    protected $countryClient;

    /**
     * @param \Spryker\Client\Country\CountryClientInterface $countryClient
     */
    public function __construct($countryClient)
    {
        $this->countryClient = $countryClient;
    }

    public function findCountriesByIso2Codes(CountryCollectionTransfer $countryCollectionTransfer): CountryCollectionTransfer
    {
        return $this->countryClient->findCountriesByIso2Codes($countryCollectionTransfer);
    }
}
