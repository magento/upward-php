<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward\Resolver;

use Magento\Upward\Definition;
use Magento\Upward\Template\Mustache;

class Template extends AbstractResolver
{
    /**
     * @var array map of template to renderer implementations
     */
    private $templateClasses = [
        'mustache' => Mustache::class
    ];

    /**
     * @inheritdoc
     */
    public function getIndicator(): string
    {
        return 'template';
    }

    public function isValid(Definition $definition): bool
    {
        if (!$definition->has('engine')) {
            return false;
        } else {
            $engine = $this->getIterator()->get('engine', $definition);
            if (!in_array($engine, $this->templateClasses)) {
                return false;
            }
        }

        return parent::isValid($definition);
    }

    /**
     * @inheritdoc
     */
    public function resolve($definition)
    {
        $template = $this->getIterator()->get('template', $definition);
        return $template;
    }
}
