<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward\Resolver;

use Magento\Upward\Definition;
use Magento\Upward\DefinitionIterator;

abstract class AbstractResolver implements ResolverInterface
{
    /**
     * @var DefinitionIterator
     */
    protected $iterator;

    /**
     * Return list of previous indicators.
     *
     * Given that the UPWARD specification is a living document, it's possible that indicators may change, but
     * to maintain backward compatibility we should still support those past indicators.
     *
     * @return string[]
     */
    public function getDeprecatedIndicators()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    abstract public function getIndicator(): string;

    /**
     * {@inheritdoc}
     */
    public function getIterator(): DefinitionIterator
    {
        return $this->iterator;
    }

    /**
     * {@inheritdoc}
     */
    public function isShorthand(string $definition): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid(Definition $definition): bool
    {
        return $definition->has($this->getIndicator());
    }

    /**
     * {@inheritdoc}
     */
    abstract public function resolve($definition);

    /**
     * {@inheritdoc}
     */
    public function setIterator(DefinitionIterator $iterator): void
    {
        $this->iterator = $iterator;
    }
}
