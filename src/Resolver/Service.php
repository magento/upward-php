<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward\Resolver;

use Magento\Upward\Definition;
use Zend\Http\Client;

class Service extends AbstractResolver
{
    /**
     * {@inheritdoc}
     */
    public function getIndicator(): string
    {
        return 'url';
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

        $headers = ['Content-type' => 'application/json'];

        $url             = $this->getIterator()->get('url', $definition);
        $query           = $this->getIterator()->get('query', $definition);
        $method          = $definition->has('method') ? $this->getIterator()->get('method', $definition) : 'POST';
        $variables       = $definition->has('variables') ? $this->getIterator()->get('variables', $definition) : [];
        $ignoreSSLErrors = $definition->has('ignoreSSLErrors')
            ? $this->getIterator()->get('ignoreSSLErrors', $definition)
            : false;
        $headers = $definition->has('headers')
            ? array_merge($headers, $this->getIterator()->get('headers', $definition))
            : $headers;
        $requestParams = [
            'query'     => $query,
            'variables' => $variables,
        ];

        $client = new Client($url, [
            'adapter'     => Client\Adapter\Curl::class,
            'curloptions' => [
                CURLOPT_SSL_VERIFYHOST => !$ignoreSSLErrors,
                CURLOPT_SSL_VERIFYPEER => !$ignoreSSLErrors,
            ],
        ]);
        $client->setMethod($method);
        $client->setHeaders($headers);
        if ($method === 'POST') {
            $client->setRawBody(json_encode($requestParams));
        } elseif ($method === 'GET') {
            $client->setParameterGet($requestParams);
        }

        $response = $client->send();

        return json_decode($response->getBody(), true);
    }
}
