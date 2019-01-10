<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward\Resolver;

use Magento\Upward\Definition;
use Magento\Upward\Template\TemplateFactory;

class Template extends AbstractResolver
{
    /**
     * {@inheritdoc}
     */
    public function getIndicator(): string
    {
        return 'engine';
    }

    public function isValid(Definition $definition): bool
    {
        if ($definition->has($this->getIndicator())) {
            $engine = $this->getIterator()->get('engine', $definition);
            try {
                TemplateFactory::get($definition->getBasepath(), $engine);
            } catch (\InvalidArgumentException $e) {
                return false;
            }
        }

        if (!$definition->has('provide')) {
            return false;
        }

        if (!$definition->has('template')) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve($definition)
    {
        $renderData = [];

        $engineValue = $definition->has('engine')
            ? $this->getIterator()->get('engine', $definition)
            : null;
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

        $engine = TemplateFactory::get($definition->getBasepath(), $engineValue);

        return $engine->render($templateValue, $renderData);
    }
}
