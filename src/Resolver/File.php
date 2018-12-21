<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward\Resolver;

use Magento\Upward\Definition;

class File extends AbstractResolver
{
    /**
     * {@inheritdoc}
     */
    public function getIndicator(): string
    {
        return 'file';
    }

    /**
     * {@inheritdoc}
     */
    public function isShorthand(string $definition): bool
    {
        return substr($definition, 0, 1) === '/'
            || substr($definition, 0, 2) === './'
            || substr($definition, 0, 3) === '../'
            || substr($definition, 0, 7) === 'file://'
            || preg_match('/^[[:alpha:]]\\:\\\\/', $definition);
    }

    /**
     * {@inheritdoc}
     */
    public function isValid(Definition $definition): bool
    {
        if (!$definition->has($this->getIndicator())) {
            return false;
        }

        if ($definition->has('encoding')) {
            $encoding = $this->getIterator()->get('encoding', $definition);

            if (!\in_array(strtolower($encoding), ['utf-8', 'latin-1', 'binary'])) {
                return false;
            }
        }

        if ($definition->has('parse')) {
            $parse = $this->getIterator()->get('parse', $definition);

            if (!\in_array(strtolower($parse), ['auto', 'text'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve($definition)
    {
        $encoding = 'utf-8';
        $parse    = 'auto';
        $path     = $definition;

        if ($definition instanceof Definition) {
            $path = $this->getIterator()->get('file', $definition);

            if ($definition->has('encoding')) {
                $encoding = $this->getIterator()->get('encoding', $definition);
            }
            if ($definition->has('parse')) {
                $parse = $this->getIterator()->get('parse', $definition);
            }
        }

        // Path is relative, expand it from definition base path.
        if (substr($path, 0, 1) === '.') {
            $path = realpath($this->getIterator()->getRootDefinition()->getBasepath() . \DIRECTORY_SEPARATOR . $path);
        }

        $value = file_get_contents($path);

        // do parsing & encoding here?
        $value;

        return $value;
    }
}
