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
     * @var array
     */
    private $lookupStack = [];

    /**
     * @var Definition
     */
    private $rootDefinition;

    public function __construct(Definition $rootDefinition, Context $context)
    {
        $this->rootDefinition = $rootDefinition;
        $this->context        = $context;
    }

    /**
     * Travserse the Definition for a value, using a resolver if necessary.
     *
     * @param string|mixed           $lookup
     * @param Definition|string|null $definition Definition to iterate rather than root definition
     *
     * @throws RuntimeException if iterator is already attempting to resolve $lookup
     *                          (ie, definition appears to contain a loop)
     * @throws RuntimeException if $lookup does not exist in definition
     */
    public function get($lookup, $definition = null)
    {
        $updateContext = false;

        if ($this->context->has($lookup)) {
            return $this->context->get($lookup);
        }

        if (\in_array($lookup, $this->lookupStack)) {
            throw new \RuntimeException('Definition appears to contain a loop: ' . json_encode($this->lookupStack));
        }

        if ($definition === null) {
            if (!$this->getRootDefinition()->has($lookup)) {
                throw new \RuntimeException(sprintf(
                    'No definition for %s',
                    \is_string($lookup) || is_numeric($lookup) ? $lookup : \gettype($lookup)
                ));
            }

            $definition    = $this->getRootDefinition()->get($lookup);
            $updateContext = true;
        }

        if ($this->context->isBuiltinValue($definition)) {
            if ($updateContext) {
                $this->context->set($lookup, $definition);
            }

            return $definition;
        }

        $lookup = $definition instanceof Definition ? $definition->getTreeAddress() : $lookup;

        $this->lookupStack[] = $lookup;

        $resolver = ResolverFactory::get($definition);

        // Treat $definition as an address for a different part of Definition tree
        if ($resolver === null && is_scalar($definition)) {
            $value = $this->get($definition);

            if ($updateContext) {
                $this->context->set($lookup, $value);
            }

            array_pop($this->lookupStack);

            return $value;
        }

        $value = $this->getFromResolver($lookup, $definition, $resolver);

        if ($updateContext) {
            $this->context->set($lookup, $value);
        }

        array_pop($this->lookupStack);

        return $value;
    }

    public function getRootDefinition(): Definition
    {
        return $this->rootDefinition;
    }

    /**
     * Get and parse a value from a resolver.
     *
     * @param Definition|string $definition
     */
    private function getFromResolver(string $lookup, $definition, Resolver\ResolverInterface $resolver)
    {
        $resolver->setIterator($this);

        $value = $resolver->resolve($definition);

        if ($value instanceof Definition) {
            $rawValue = [];

            foreach ($value->getKeys() as $key) {
                $childDefinition = $value->get($key);
                $fullKey         = $key;

                if (is_scalar($childDefinition)) {
                    $fullKey = $lookup . '.' . $key;
                }

                $rawValue[$key] = $this->get($fullKey, $childDefinition);
            }

            $value = $rawValue;
        }

        return $value;
    }
}
