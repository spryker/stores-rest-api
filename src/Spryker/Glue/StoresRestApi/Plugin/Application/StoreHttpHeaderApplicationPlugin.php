<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\StoresRestApi\Plugin\Application;

use Spryker\Glue\Kernel\AbstractPlugin;
use Spryker\Service\Container\ContainerInterface;
use Spryker\Shared\ApplicationExtension\Dependency\Plugin\ApplicationPluginInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \Spryker\Glue\StoresRestApi\StoresRestApiFactory getFactory()
 */
class StoreHttpHeaderApplicationPlugin extends AbstractPlugin implements ApplicationPluginInterface
{
    /**
     * @var string
     */
    protected const HEADER_STORE_NAME = 'Store';

    /**
     * @var string
     */
    protected const SERVICE_STORE = 'store';

    /**
     * {@inheritDoc}
     * - Gets store name from the Request header.
     *
     * @param \Spryker\Service\Container\ContainerInterface $container
     *
     * @return \Spryker\Service\Container\ContainerInterface
     */
    public function provide(ContainerInterface $container): ContainerInterface
    {
        $container->set(static::SERVICE_STORE, function () {
            $request = Request::createFromGlobals();

            return $request->headers->get(static::HEADER_STORE_NAME);
        });

        return $container;
    }
}
