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
        $engineName     = $definition->has('engine') ? $this->getIterator()->get('engine', $definition) : null;
        $templateString = $this->getIterator()->get('template', $definition);
        $renderData     = [];

        if ($definition->has('provide')) {
            foreach ($definition->get('provide') as $index => $provideDefinition) {
                $key              = \is_int($index) ? $provideDefinition : $index;
                $renderData[$key] = $provideDefinition instanceof Definition
                    ? $this->getIterator()->get('provide.' . $index)
                    : $this->getIterator()->get($provideDefinition);
            }
        }

        $engine = TemplateFactory::get($this->getIterator()->getRootDefinition()->getBasepath(), $engineName);

        return $engine->render($templateString, $renderData);
    }
}
