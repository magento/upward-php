<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward\Resolver;

class Inline extends AbstractResolver
{
    /**
     * {@inheritdoc}
     */
    public function getIndicator(): string
    {
        return 'inline';
    }

    /**
     * {@inheritdoc}
     */
    public function resolve($definition)
    {
        return $definition->get($this->getIndicator());
    }
}
