<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward;

class ResolverFactory
{
    public const RESOLVER_TYPE_INLINE = 'inline';

    private $resolvers = [
        self::RESOLVER_TYPE_INLINE => Resolver\Inline::class,
    ];

    public function get($definition): ?Resolver\ResolverInterface
    {
    }
}
