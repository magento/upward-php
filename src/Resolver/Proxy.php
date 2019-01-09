<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward\Resolver;

use Magento\Upward\Definition;
use Zend\Http\Client;

class Proxy extends AbstractResolver
{
    /**
     * {@inheritdoc}
     */
    public function getIndicator(): string
    {
        return 'proxy';
    }

    /**
     * {@inheritdoc}
     */
    public function isValid(Definition $definition): bool
    {
        $rootDefinition = $this->getIterator()->getRootDefinition();
        if ($rootDefinition->has('proxy')) {
            $proxyDefinition = $rootDefinition->get('proxy');
            if (!$proxyDefinition->has('target')) {
                return false;
            }
        } else {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve($definition)
    {
        $target          = $this->getIterator()->get('proxy.target');
        $ignoreSSLErrors = $this->getIterator()->getRootDefinition()->has('proxy.ignoreSSLErrors')
            ? $this->getIterator()->get('proxy.ignoreSSLErrors')
            : false;
        $request = new \Zend\Http\PhpEnvironment\Request();
        $request->setUri($target);
        if ($request->getHeaders()->has('Host')) {
            $request->getHeaders()->removeHeader($request->getHeader('Host'));
            $request->getHeaders()->addHeaderLine('Host', parse_url($target, PHP_URL_HOST));
        }

        $client = new Client(null, [
            'adapter'     => Client\Adapter\Curl::class,
            'curloptions' => [
                CURLOPT_SSL_VERIFYPEER => !$ignoreSSLErrors,
            ],
        ]);

        return $client->send($request);
    }
}
