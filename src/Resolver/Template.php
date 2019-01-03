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
    const TEMPLATE_MUSTACHE = 'mustache';
    const DEFAULT_TEMPLATE_ENGINE = self::TEMPLATE_MUSTACHE;

    /**
     * @var array map of template to renderer implementations
     */
    private $templateClasses = [
        self::TEMPLATE_MUSTACHE => Mustache::class
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
        if ($definition->has('engine')) {
            $engine = $this->getIterator()->get('engine', $definition);
            if (!in_array($engine, array_keys($this->templateClasses))) {
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
        $engineValue = $definition->has('engine')
            ? $this->getIterator()->get('engine', $definition)
            : self::DEFAULT_TEMPLATE_ENGINE;
        $templateValue = $this->getIterator()->get('template', $definition);
        $engine = new $this->templateClasses[$engineValue]();

        return $engine->render($templateValue, ['title' => 'My Awesome Title']);
    }
}
