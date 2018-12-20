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
     * @param string|mixed           $lookup
     * @param bool                   $updateContext Store result from Definition & Resolver in Context
     *
     * @throws RuntimeException if iterator is already attempting to resolve $lookup
     *                          (ie, definition appears to contain a loop)
     * @throws RuntimeException if $lookup does not exist in definition
     */
    public function get($lookup, $definition = null) {
        $updateContext = false;

        if ($this->context->has($lookup)) {
            return $this->context->get($lookup);
        }

        if (\in_array($lookup, $this->lookupStack)) {
            throw new \RuntimeException('Definition appears to contain a loop: ' . json_encode($this->lookupStack));
        }

        if ($definition === null) {
            $definition = $this->getRootDefinition()->get($lookup);
            $updateContext = true;
        }

        if ($definition === null) {
            throw new \RuntimeException('No definition for ' . (is_scalar($lookup) ? $lookup : \gettype($lookup)));
        }

        if ($this->context->isBuiltinValue($definition)) {
            if ($updateContext) {
                $this->context->set($lookup, $definition);
            }

            return $definition;
        }

        $this->lookupStack[] = $definition->getLookupPath();

        $resolver = ResolverFactory::get($definition);

        if ($resolver === null && is_scalar($definition)) {
            $value = $this->get($definition);

            array_pop($this->lookupStack);

            return $value;
        }

        $resolver->setIterator($this);

        $value = $resolver->resolve($definition);

        // Need to refactor this in the future; there's too much swapping between Definition & array types.
        if ($value instanceof Definition) {
            $value = $value->toArray();

            array_walk($value, function (&$childValue, $key) use ($lookup): void {
                $childDefinition = is_scalar($childValue) ? $childValue : new Definition($childValue);

                $childValue = $this->get($key, $lookup, $childDefinition, false);
            });
        }

        if ($updateContext) {
            $this->context->set($lookup, $value);
        }

        array_pop($this->lookupStack);

        return $value;
    }

    public function getRootDefinition(): Definition
    {
        return $this->definition;
    }
}
