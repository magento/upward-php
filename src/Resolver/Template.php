<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward\Resolver;

use Magento\Upward\AbstractKeyValueStore;
use Magento\Upward\Definition;
use Magento\Upward\Template\TemplateFactory;

class Template extends AbstractResolver
{
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
            try {
                TemplateFactory::get($definition->getBasepath(), $engine);
            } catch (\InvalidArgumentException $e) {
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
        $engineName     = $definition->has('engine') ? $this->getIterator()->get('engine', $definition) : null;
        $templateString = $this->getIterator()->get('template', $definition);
        $renderData     = $this->getIterator()->get('provide', $definition);

        if ($definition->get('provide')->isList()) {
            $renderData = array_combine($definition->get('provide')->toArray(), $renderData);
        }

        $engine = TemplateFactory::get($definition->getBasepath(), $engineName);

        return $engine->render($templateString, $renderData);
    }
}
