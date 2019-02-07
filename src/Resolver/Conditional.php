<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward\Resolver;

use Magento\Upward\Definition;

class Conditional extends AbstractResolver
{
    /**
     * {@inheritdoc}
     */
    public function getIndicator(): string
    {
        return 'when';
    }

    /**
     * {@inheritdoc}
     */
    public function isValid(Definition $definition): bool
    {
        if (!$definition->has($this->getIndicator())) {
            return false;
        }

        if (!$definition->has('default')) {
            return false;
        }

        foreach ($definition->get($this->getIndicator()) as $matcher) {
            if (!$matcher instanceof Definition) {
                return false;
            }

            if (!$matcher->has('matches') || !\is_string($matcher->get('matches'))) {
                return false;
            }

            if (!$matcher->has('pattern') || !\is_string($matcher->get('pattern'))) {
                return false;
            }

            if (!$matcher->has('use')) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve($definition)
    {
        if (!$definition instanceof Definition) {
            throw new \InvalidArgumentException('$definition must be an instance of ' . Definition::class);
        }

        foreach ($definition->get($this->getIndicator()) as $matcher) {
            try {
                $rawValue = $this->getIterator()->get($matcher->get('matches'));
            } catch (\Exception $exception) {
                // exceptions thrown for match value lookups should silently fail and fall through
                continue;
            }
            $value   = is_scalar($rawValue) ? (string) $rawValue : json_encode($rawValue);
            $pattern = '/' . addcslashes($matcher->get('pattern'), '/') . '/';

            if (preg_match($pattern, $value, $matches)) {
                // preface each key with '$'
                $matches = array_combine(array_map(function ($key) {
                    return '$' . $key;
                }, array_keys($matches)), $matches);

                // $matches is a temporary context value, so need a unique clone of Iterator & Context
                $iterator = clone $this->getIterator();
                $iterator->getContext()->set('$match', $matches, false);

                return $iterator->get('use', $matcher);
            }
        }

        return $this->getIterator()->get('default', $definition);
    }
}
