<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward;

class DefinitionIterator
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @var Definition
     */
    private $definition;

    /**
     * @var array
     */
    private $lookupStack = [];

    public function __construct(Definition $definition, Context $context)
    {
        $this->definition = $definition;
        $this->context    = $context;
    }

    /**
     * Travserse the Definition for a value, using a resolver if necessary.
     *
     * @param string|mixed $lookup
     * @param bool         $updateContext Store result from Definition & Resolver in Context
     *
     * @throws RuntimeException if iterator is already attempting to resolve $lookup
     *                          (ie, definition appears to contain a loop)
     * @throws RuntimeException if $lookup does not exist in definition
     */
    public function get($lookup, bool $updateContext = true)
    {
        if ($this->context->has($lookup)) {
            return $this->context->get($lookup);
        }

        if (\in_array($lookup, $this->lookupStack)) {
            throw new \RuntimeException('Definition appears to contain a loop: ' . json_encode($this->lookupStack));
        }

        $this->lookupStack[] = $lookup;

        $definition = $this->definition->get($lookup);

        if ($definition === null) {
            throw new \RuntimeException('No definition for ' . (is_scalar($lookup) ? $lookup : \gettype($lookup)));
        }

        $resolver = ResolverFactory::get($definition);

        if ($resolver === null && is_scalar($definition)) {
            $value = $this->get($definition);

            array_pop($this->lookupStack);

            return $value;
        }

        $resolver->setIterator($this);

        $value = $resolver->resolve($definition);

        if ($value instanceof Definition) {
            $value = $value->toArray();

            array_walk($value, function (&$value, $key) use ($lookup): void {
                // Don't update the Context; we'll take care of that below
                $value = $this->get($lookup . '.' . $key, false);
            });
        }

        if ($updateContext) {
            $this->context->set($lookup, $value);
        }

        array_pop($this->lookupStack);

        return $value;
    }
}
