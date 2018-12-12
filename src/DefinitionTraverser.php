<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward;

class DefinitionTraverser
{
    private $context;

    private $definition;

    public function __construct(Definition $definition, Context $requestContext)
    {
        $this->definition     = $definition;
        $this->requestContext = $requestContext;
    }

    public function get(string $lookup)
    {
    }
}
