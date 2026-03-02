<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\StoresRestApi\Processor\Mapper;

use Generated\Shared\Transfer\CountryTransfer;
use Generated\Shared\Transfer\StoreCountryRestAttributesTransfer;

interface StoresCountryResourceMapperInterface
{
    public function mapCountryToStoresCountryRestAttributes(CountryTransfer $countryTransfer): StoreCountryRestAttributesTransfer;
}
