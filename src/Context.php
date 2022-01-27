<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward;

use Laminas\Http\PhpEnvironment\Request;

class Context extends AbstractKeyValueStore
{
    /**
     * Static values that should always return as-is.
     */
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

    /** @var string[] */
    private $unsetOnClone = [];

    /**
     * Do not propagate non-cloneable keys.
     */
    public function __clone()
    {
        foreach ($this->unsetOnClone as $key) {
            unset($this->data[$key]);
        }
    }

    /**
     * Instantiate a Context from a Zend Http Request.
     */
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
            'env' => getenv(),
        ]);
    }

    /**
     * If $lookup is a builtin value, return it directly.
     *
     * {@inheritdoc}
     */
    public function get($lookup)
    {
        if ($this->isBuiltinValue($lookup)) {
            return $lookup;
        }

        return parent::get($lookup);
    }

    /**
     * Is $lookup a builtin value or set in the store?
     *
     * {@inheritdoc}
     */
    public function has($lookup): bool
    {
        return $this->isBuiltinValue($lookup) || parent::has($lookup);
    }

    /**
     * Is $value a built in constant or HTTP status code?
     */
    public function isBuiltinValue($value): bool
    {
        return \in_array($value, self::BUILTIN_VALUES, true) || $this->isStatusCode($value);
    }

    /**
     * Is $value an HTTP status code?
     */
    public function isStatusCode($value): bool
    {
        return (\is_int($value) || (\is_string($value) && ctype_digit($value))) && 100 <= $value && $value < 600;
    }

    /**
     * @throws RuntimeException if $lookup is a built in value
     *
     * {@inheritdoc}
     */
    public function set(string $lookup, $value, bool $cloneable = true): void
    {
        if ($this->isBuiltinValue($lookup)) {
            throw new \RuntimeException('Cannot override a builtin value.');
        }

        if (!$cloneable) {
            $this->unsetOnClone[] = $lookup;
        }

        parent::set($lookup, $value);
    }

    /**
     * Convert an an array of associative arrays to ['key' => $key, 'value' => $value].
     */
    private static function assocToEntries(array $array): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $result[] = compact('key', 'value');
        }

        return $result;
    }
}
