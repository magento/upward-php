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
    public const DEFAULT_TEMPLATE_ENGINE = self::TEMPLATE_MUSTACHE;
    public const TEMPLATE_MUSTACHE       = 'mustache';

    /**
     * @var array map of template to renderer implementations
     */
    private $templateClasses = [
        self::TEMPLATE_MUSTACHE => Mustache::class,
    ];

    /**
     * {@inheritdoc}
     */
    public function getIndicator(): string
    {
        return 'template';
    }

    public function isValid(Definition $definition): bool
    {
        if ($definition->has('engine')) {
            $engine = $this->getIterator()->get('engine', $definition);
            if (!array_key_exists($engine, $this->templateClasses)) {
                return false;
            }
        }

        if (!$definition->has('provide')) {
            return false;
        }

        return parent::isValid($definition);
    }

    /**
     * {@inheritdoc}
     */
    public function resolve($definition)
    {
        $renderData = [];

        $engineValue = $definition->has('engine')
            ? $this->getIterator()->get('engine', $definition)
            : self::DEFAULT_TEMPLATE_ENGINE;
        $templateValue = $this->getIterator()->get('template', $definition);
        /** @var Definition $provideValue */
        $provideValue = $definition->get('provide');

        if ($provideValue->has('resolver')) {
            $renderData = $this->getIterator()->get('provide', $definition);
        } else {
            $provideKeys = $provideValue->toArray();
            foreach ($provideKeys as $definitionKey) {
                $renderData[$definitionKey] = $this->getIterator()->get($definitionKey);
                if ($definitionKey === 'env') {
                    $renderData[$definitionKey] = $renderData[$definitionKey]->toArray();
                }
            }
        }

        $engine = new $this->templateClasses[$engineValue]($definition->getBasepath());

        return $engine->render($templateValue, $renderData);
    }
}
