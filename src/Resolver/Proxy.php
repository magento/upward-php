<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward\Resolver;

use Laminas\Http\Client;
use Magento\Upward\Definition;

class Proxy extends AbstractResolver
{
    /**
     * {@inheritdoc}
     */
    public function getIndicator(): string
    {
        return 'target';
    }

    /**
     * {@inheritdoc}
     */
    public function isValid(Definition $definition): bool
    {
        if ($definition->has('ignoreSSLErrors')) {
            $ignoreSSLErrors = $this->getIterator()->get('ignoreSSLErrors', $definition);
            if (!\is_bool($ignoreSSLErrors)) {
                return false;
            }
        }

        return parent::isValid($definition);
    }

    /**
     * {@inheritdoc}
     */
    public function resolve($definition)
    {
        if (!$definition instanceof Definition) {
            throw new \InvalidArgumentException('$definition must be an instance of ' . Definition::class);
        }

        $target          = $this->getIterator()->get('target', $definition);
        $ignoreSSLErrors = $definition->has('ignoreSSLErrors')
            ? $this->getIterator()->get('ignoreSSLErrors', $definition)
            : false;
        $request            = new \Laminas\Http\PhpEnvironment\Request();
        $originalRequestURI = clone $request->getUri();
        $request->setUri($target);
        $request->getUri()->setPath($originalRequestURI->getPath())->setQuery($originalRequestURI->getQuery());
        $requestHeaders = $request->getHeaders();
        if ($requestHeaders && $requestHeaders->has('Host')) {
            $requestHeaders->removeHeader($request->getHeader('Host'));
            $requestHeaders->addHeaderLine('Host', parse_url($target, \PHP_URL_HOST));
        }

        $client = new Client(null, [
            'adapter'     => Client\Adapter\Curl::class,
            'curloptions' => [
                \CURLOPT_SSL_VERIFYHOST => $ignoreSSLErrors ? 0 : 2,
                \CURLOPT_SSL_VERIFYPEER => !$ignoreSSLErrors,
            ],
        ]);

        return $client->send($request);
    }
}
