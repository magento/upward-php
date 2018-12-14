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
                'headers'       => $request->getHeaders()->toArray(),
                'headerEntries' => self::assocToEntries($request->getHeaders()->toArray()),
                'queryEntries'  => self::assocToEntries($request->getQuery()->toArray()),
                'url'           => [
                    'host'     => $request->getUri()->getHost(),
                    'hostname' => $request->getUri()->getHost() . ':' . $request->getUri()->getPort(),
                    'port'     => $request->getUri()->getPort(),
                    'pathname' => $request->getUri()->getPath(),
                    'search'   => '?' . $request->getUri()->getQuery(),
                    'query'    => $request->getQuery()->toArray(),
                ],
            ],
            'env' => $request->getEnv()->toArray(),
        ]);
    }

    public function get($lookup)
    {
        if ($this->isBuiltinValue($lookup)) {
            return $lookup;
        }

        return parent::get($lookup);
    }

    public function set($lookup, $value): void
    {
        if ($this->isBuiltinValue($lookup)) {
            throw new \RuntimeException('Cannot override a builtin value.');
        }

        parent::set($lookup, $value);
    }

    private static function assocToEntries(array $array): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $result[] = compact('key', 'value');
        }

        return $result;
    }

    private function isBuiltinValue($value): bool
    {
        return \in_array($value, self::BUILTIN_VALUES, true) || $this->isStatusCode($value);
    }

    private function isStatusCode($value): bool
    {
        return (\is_int($value) || ctype_digit($value)) && 100 <= $value && $value < 600;
    }
}
