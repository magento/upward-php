<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward;

use Laminas\Stdlib\ArrayUtils;

abstract class AbstractKeyValueStore implements \JsonSerializable, \Countable, \Iterator
{
    /**
     * @var array
     */
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function count(): int
    {
        return \count($this->data);
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->get($this->key());
    }

    /**
     * Get value for a key.
     *
     * Scalar values will be returned directly, complex values will be returned as an instance of this class.
     *
     * @param string|mixed $lookup
     *
     * @return mixed|static|null
     */
    public function get($lookup)
    {
        if (empty($lookup) && $lookup != 0) {
            return;
        }

        $value = $this->data;

        foreach (explode('.', (string) $lookup) as $segment) {
            if (\is_array($value) && \array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return;
            }
        }

        return \is_array($value) ? new static($value) : $value;
    }

    /**
     * Find the longest portion of $lookup that exists.
     */
    public function getExistingParentLookup(string $lookup): string
    {
        $subArray = $this->data;

        $parentSegments = [];

        foreach (explode('.', $lookup) as $segment) {
            if (\is_array($subArray)) {
                if (\array_key_exists($segment, $subArray)) {
                    $subArray         = $subArray[$segment];
                    $parentSegments[] = $segment;
                } else {
                    break;
                }
            } else {
                break;
            }
        }

        return implode('.', $parentSegments);
    }

    /**
     * List keys.
     */
    public function getKeys(): array
    {
        return array_keys($this->data);
    }

    /**
     * Does $lookup exist in this store?
     *
     * @param string|mixed $lookup
     */
    public function has($lookup): bool
    {
        $subArray = $this->data;

        foreach (explode('.', (string) $lookup) as $segment) {
            if (\is_array($subArray) && \array_key_exists($segment, $subArray)) {
                $subArray = $subArray[$segment];
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Are all the keys sequential numeric values?
     */
    public function isList(): bool
    {
        return ArrayUtils::isList($this->data);
    }

    /**
     * Data to include when serializing to JSON.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        return key($this->data);
    }

    public function next(): void
    {
        next($this->data);
    }

    public function rewind(): void
    {
        reset($this->data);
    }

    /**
     * Assign a new key in store.
     *
     * @throws RuntimeException if $lookup is empty
     * @throws RuntimeException if $lookup is already set
     * @throws RuntimeException if an existing parent of lookup is a scalar value
     *                          (would effectively overwrite an existing value)
     */
    public function set(string $lookup, $value): void
    {
        $lookup = trim($lookup);

        if (empty($lookup)) {
            throw new \RuntimeException('Cannot set a value for an empty lookup.');
        }

        $segments = explode('.', $lookup);
        $data     = &$this->data;

        if ($value instanceof self) {
            $value = $value->toArray();
        }

        while (\count($segments) > 1) {
            $segment = array_shift($segments);

            if (!isset($data[$segment])) {
                $data[$segment] = [];
            } elseif (!\is_array($data[$segment])) {
                throw new \RuntimeException('Lookup would overwrite existing scalar value with an array.');
            }

            $data = &$data[$segment];
        }

        $key = array_shift($segments);
        if (\array_key_exists($key, $data)) {
            // $value should be appended w/o modifying any of the existing values in $data
            if (\is_array($data[$key]) && \is_array($value)) {
                $data[$key] = $data[$key] + $value;

                return;
            }

            throw new \RuntimeException('Lookup already exists in store.');
        }

        $data[$key] = $value;
    }

    /**
     * Convert store data to bare array.
     */
    public function toArray(): array
    {
        return $this->data;
    }

    public function valid(): bool
    {
        return $this->key() !== null && $this->has($this->key());
    }
}
