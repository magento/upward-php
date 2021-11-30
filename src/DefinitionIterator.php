<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward;

use Laminas\Http\Response;

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

    public function __construct(
        Definition $rootDefinition,
        Context $context,
        ?array $additionalResolvers = []
    ) {
        $this->rootDefinition = $rootDefinition;
        $this->context        = $context;

        if (!empty($additionalResolvers)) {
            foreach ($additionalResolvers as $type => $class) {
                ResolverFactory::addResolverClass($type, $class);
            }
        }
    }

    /**
     * Make a copy of Context in clone.
     */
    public function __clone()
    {
        $this->context = clone $this->context;
    }

    /**
     * Traverse the Definition for a value, using a resolver if necessary.
     *
     * @param string|mixed           $lookup
     * @param Definition|string|null $definition Definition to iterate rather than root definition
     *
     * @throws \RuntimeException if iterator is already attempting to resolve $lookup
     *                           (ie, definition appears to contain a loop)
     * @throws \RuntimeException if $lookup does not exist in definition
     * @throws \Exception        if lookup is undefined in the context
     */
    public function get($lookup, $definition = null)
    {
        $updateContext  = false;
        $originalLookup = '';

        if ($definition === null && $this->isContextFullyPopulated($lookup)) {
            $value = $this->context->get($lookup);

            return ($value instanceof Context) ? $value->toArray() : $value;
        }

        if ($definition === null) {
            $definition    = $this->getRootDefinition();
            $updateContext = true;
        }

        $definedValue = $definition;

        if ($definition instanceof Definition) {
            if (!$definition->has($lookup)) {
                if ($parentLookup = $definition->getExistingParentLookup($lookup)) {
                    $originalLookup = $lookup;
                    $lookup         = $parentLookup;
                } else {
                    throw new \RuntimeException(sprintf('No definition for %s', \is_string($lookup) || is_numeric($lookup) ? $lookup : \gettype($lookup)));
                }
            }

            $definedValue = $definedValue->get($lookup);
        }

        $fullLookup = empty($definition->getTreeAddress()) ? $lookup : $definition->getTreeAddress() . '.' . $lookup;

        if (\in_array($fullLookup, $this->lookupStack)) {
            $stack = array_merge($this->lookupStack, [$lookup]);
            throw new \RuntimeException('Definition appears to contain a loop: ' . json_encode($stack));
        }

        $this->lookupStack[] = $fullLookup;

        try {
            $value = $this->getFromDefinedValue($lookup, $definedValue);
        } catch (\Exception $e) {
            array_pop($this->lookupStack);

            throw $e;
        }

        if ($updateContext) {
            $this->context->set($lookup, $value);
        }

        array_pop($this->lookupStack);

        if (!empty($originalLookup)) {
            if (\is_array($value)) {
                $value = $this->get($originalLookup);
            } elseif (!$value instanceof Response) {
                throw new \RuntimeException(sprintf('Could not get nested value %s from value of type %s', $originalLookup, \gettype($value)));
            }
        }

        return $value;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getRootDefinition(): Definition
    {
        return $this->rootDefinition;
    }

    /**
     * Check if $lookup exists in Context, and if so that all of it's values have been loaded from the Definition.
     */
    public function isContextFullyPopulated($lookup): bool
    {
        if (!$this->context->has($lookup)) {
            return false;
        }

        if (!$this->getRootDefinition()->has($lookup)) {
            return true;
        }

        $value = $this->context->get($lookup);

        if (!$value instanceof Context || !$value->isList()) {
            return true;
        }

        $definition = $this->getRootDefinition()->get($lookup);

        if (!$definition instanceof Definition || !$definition->isList()) {
            return true;
        }

        return $value->count() >= $definition->count();
    }

    /**
     * Get result for defined value, whether it's built in, a (traversable list of) lookup(s), or resolvable.
     *
     * @param string|Definition $definedValue
     */
    private function getFromDefinedValue(string $lookup, $definedValue)
    {
        if ($this->context->isBuiltinValue($definedValue)) {
            return $definedValue;
        }

        if ($definedValue instanceof Definition && $definedValue->isList()) {
            return array_map(function ($key) {
                return $this->get($key);
            }, $definedValue->toArray());
        }

        $resolver = ResolverFactory::get($definedValue);

        return ($resolver === null && is_scalar($definedValue))
            ? $this->get($definedValue) // Treat $definedValue as an address for a different part of Definition tree
            : $this->getFromResolver($lookup, $definedValue, $resolver);
    }

    /**
     * Get and parse a value from a resolver.
     *
     * @param Definition|string $definedValue
     *
     * @throws \RuntimeException if Definition is not valid
     */
    private function getFromResolver(string $lookup, $definedValue, Resolver\ResolverInterface $resolver)
    {
        $resolver->setIterator($this);

        if ($definedValue instanceof Definition && !$resolver->isValid($definedValue)) {
            throw new \RuntimeException(sprintf('Definition %s is not valid for %s.', json_encode($definedValue), \get_class($resolver)));
        }

        $value = $resolver->resolve($definedValue);

        if ($value instanceof Definition) {
            $rawValue = [];

            foreach ($value->getKeys() as $key) {
                $rawValue[$key] = $this->get($key, $value);
            }

            $value = $rawValue;
        }

        return $value;
    }
}
