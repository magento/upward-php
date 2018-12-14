<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward\Resolver;

use Magento\Upward\Definition;
use Magento\Upward\DefinitionIterator;

interface ResolverInterface
{
    /**
     * Get a definition key that would uniquely identify this resolver.
     */
    public function getIndicator(): string;

    /**
     * Get the DefinitionIterator for this resolver.
     */
    public function getIterator(): DefinitionIterator;

    /**
     * Is a passed value short hand for this resolver definition.
     */
    public function isShorthand(string $definition): bool;

    /**
     * Is a passed definition valid for this resolver.
     */
    public function isValid(Definition $definition): bool;

    /**
     * Resolve a definition to its value.
     *
     * @param Definition|string $definition
     *
     * @return GuzzleHttp\Promise\PromiseInterface|mixed
     */
    public function resolve($definition);

    /**
     * Assign the DefinitionIterator for this resolver.
     */
    public function setIterator(DefinitionIterator $iterator): void;
}
