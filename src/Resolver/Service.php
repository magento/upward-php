<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward\Resolver;

use Laminas\Http\Client;
use Magento\Upward\Definition;

class Service extends AbstractResolver
{
    public const DEPRECATED_URL_INDICATOR = 'url';

    /**
     * {@inheritdoc}
     */
    public function getDeprecatedIndicators()
    {
        return [self::DEPRECATED_URL_INDICATOR];
    }

    /**
     * {@inheritdoc}
     */
    public function getIndicator(): string
    {
        return 'endpoint';
    }

    /**
     * {@inheritdoc}
     */
    public function isValid(Definition $definition): bool
    {
        if (!$definition->has('query')) {
            return false;
        }

        if ($definition->has('method')) {
            $method = $this->getIterator()->get('method', $definition);
            if (!\in_array($method, ['GET', 'POST'])) {
                return false;
            }
        }

        return $definition->has($this->getIndicator()) xor $definition->has(self::DEPRECATED_URL_INDICATOR);
    }

    /**
     * {@inheritdoc}
     */
    public function resolve($definition)
    {
        if (!$definition instanceof Definition) {
            throw new \InvalidArgumentException('$definition must be an instance of ' . Definition::class);
        }

        $urlParameter = $definition->has($this->getIndicator())
            ? $this->getIndicator()
            : self::DEPRECATED_URL_INDICATOR;

        $url             = $this->getIterator()->get($urlParameter, $definition);
        $query           = $this->getIterator()->get('query', $definition);
        $method          = $definition->has('method') ? $this->getIterator()->get('method', $definition) : 'POST';
        $variables       = $definition->has('variables') ? $this->getIterator()->get('variables', $definition) : [];
        $ignoreSSLErrors = $definition->has('ignoreSSLErrors')
            ? $this->getIterator()->get('ignoreSSLErrors', $definition)
            : false;
        $requestParams = [
            'query'     => $query,
            'variables' => $variables,
        ];

        $client = new Client($url, [
            'adapter'     => Client\Adapter\Curl::class,
            'curloptions' => [
                \CURLOPT_SSL_VERIFYHOST => $ignoreSSLErrors ? 0 : 2,
                \CURLOPT_SSL_VERIFYPEER => !$ignoreSSLErrors,
            ],
        ]);

        $client->setMethod($method);

        if ($method === 'POST') {
            $headers = $definition->has('headers')
                ? array_merge(['Content-type' => 'application/json'], $this->getIterator()->get('headers', $definition))
                : ['Content-type' => 'application/json'];

            $client->setRawBody(json_encode($requestParams));
        } elseif ($method === 'GET') {
            $headers = $definition->has('headers') ? $this->getIterator()->get('headers', $definition) : [];

            $client->setParameterGet($requestParams);
        }

        $client->setHeaders($headers);

        $response = $client->send();

        return json_decode($response->getBody(), true);
    }
}
