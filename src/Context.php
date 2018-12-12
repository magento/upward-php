<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward;

use Zend\Http\PhpEnvironment\Request;

class Context extends AbstractKeyValueStore
{
    public const BUILTIN_VALUES = [
        true,
        false,
        'GET',
        'POST',
        'mustache',
        'text/html',
        'text/plain',
        'application/json',
        'utf-8',
        'utf8',
        'latin-1',
        'base64',
        'binary',
        'hex',
    ];

    public static function fromRequest(Request $request): self
    {
        return new self([
            'request' => [
                'headers'       => $request->getHeaders(),
                'headerEntries' => self::assocToEntries($request->getHeaders()),
                'queryEntries'  => self::assocToEntries($request->getQuery()),
                'url'           => [
                    'host'     => $request->getUri()->getHost(),
                    'hostname' => $request->getUri()->getHost() . ':' . $request->getUri()->getPort(),
                    'port'     => $request->getUri()->getPort(),
                    'pathname' => $request->getUri()->getPath(),
                    'search'   => '?' . $request->getUri()->getQuery(),
                    'query'    => $request->getQuery(),
                ],
            ],
            'env' => $request->getEnv(),
        ]);
    }

    public function get(string $lookup)
    {
        if ($this->isBuiltinValue($lookup)) {
            return $lookup;
        }

        return parent::get($lookup);
    }

    public function set(string $lookup, $value): self
    {
        if ($this->isBuiltinValue($lookup)) {
            throw new \RuntimeException('Cannot override a builtin value.');
        }

        return parent::set($lookup, $value);
    }

    private static function assocToEntries(array $array): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $result[] = compact('key', 'value');
        }

        return $result;
    }

    private function isBuiltinValue(string $value)
    {
        return array_key_exists($value, self::BUILTIN_VALUES) || $this->isStatusCode($value);
    }

    private function isStatusCode(string $value): bool
    {
        return ctype_digit($value) && 100 <= $value && $value < 600;
    }
}
