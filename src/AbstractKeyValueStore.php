<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward;

abstract class AbstractKeyValueStore
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function get(string $lookup)
    {
        if ($lookup === '') {
            return;
        }

        $value = $this->data;

        foreach (explode('.', $lookup) as $segment) {
            if (\is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return;
            }
        }

        return \is_array($value) ? new static($value) : $value;
    }

    public function has(string $lookup): bool
    {
        $subArray = $this->data;

        foreach (explode('.', $lookup) as $segment) {
            if (\is_array($subArray) && array_key_exists($segment, $subArray)) {
                $subArray = $subArray[$segment];
            } else {
                return false;
            }
        }

        return true;
    }

    public function set(string $lookup, $value): self
    {
        if ($lookup === '') {
            throw new \RuntimeException('Nah, do not set the entire data.');
        }

        $segments = explode('.', $lookup);
        $data     = &$this->data;

        while (\count($segments) > 1) {
            $segment = array_shift($segments);

            if (!isset($data[$segment])) {
                $data[$segment] = [];
            } elseif (!\is_array($data[$segment])) {
                throw new \RuntimeException('Changing scalar value to array? No.');
            }

            $data = &$data[$segment];
        }

        $key = array_shift($segments);
        if (array_key_exists($key, $data)) {
            throw new \RuntimeException('No overwriting existing values');
        }

        $data[$key] = $value;

        return $this;
    }
}
