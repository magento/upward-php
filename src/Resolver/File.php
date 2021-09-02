<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward\Resolver;

use Laminas\Http\Response;
use Magento\Upward\Definition;

class File extends AbstractResolver
{
    /**
     * Possible values for encoding.
     */
    public const VALID_ENCODING_VALUES = ['utf-8', 'latin-1', 'binary'];

    /**
     * Possible values for parse.
     */
    public const VALID_PARSE_VALUES = ['auto', 'text', 'json', 'mustache', 'graphql'];

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
        if ($definition->has('encoding')) {
            $encoding = $this->getIterator()->get('encoding', $definition);

            if (!\in_array(strtolower($encoding), self::VALID_ENCODING_VALUES)) {
                return false;
            }
        }

        if ($definition->has('parse')) {
            $parse = $this->getIterator()->get('parse', $definition);

            if (!\in_array(strtolower($parse), self::VALID_PARSE_VALUES)) {
                return false;
            }
        }

        return parent::isValid($definition);
    }

    /**
     * {@inheritdoc}
     */
    public function resolve($definition)
    {
        $encoding   = 'utf-8';
        $parse      = 'auto';
        $path       = $definition;
        $upwardRoot = $this->getIterator()->getRootDefinition()->getBasepath();

        if ($definition instanceof Definition) {
            $path = $this->getIterator()->get('file', $definition);

            if ($definition->has('encoding')) {
                $encoding = $this->getIterator()->get('encoding', $definition);
            }
            if ($definition->has('parse')) {
                $parse = $this->getIterator()->get('parse', $definition);
            }
        }

        $path = realpath($upwardRoot . \DIRECTORY_SEPARATOR . $path);

        if (!$path || strpos($path, $upwardRoot) !== 0) {
            $response = new Response();
            $response->setStatusCode(Response::STATUS_CODE_404);

            return $response;
        }

        $content = file_get_contents($path);

        if (($parse == 'auto' && pathinfo($path, \PATHINFO_EXTENSION) == 'json') || $parse == 'json') {
            $content = json_decode($content, true);

            if (json_last_error() !== \JSON_ERROR_NONE) {
                throw new \RuntimeException('Failed to parse ' . basename($path) . ': ' . json_last_error_msg());
            }
        }

        return $content;
    }
}
