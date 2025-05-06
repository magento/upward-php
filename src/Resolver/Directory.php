<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward\Resolver;

use Laminas\Http\Header\ContentType;
use Laminas\Http\Response;
use Laminas\Http\Response\Stream;
use Magento\Upward\Definition;
use Mimey\MimeTypes;

class Directory extends AbstractResolver
{
    /**
     * @inheritdoc
     */
    public function getIndicator(): string
    {
        return 'directory';
    }

    /**
     * @inheritdoc
     */
    public function isValid(Definition $definition): bool
    {
        if (!$definition->has($this->getIndicator())) {
            return false;
        }

        $directory  = $this->getIterator()->get('directory', $definition);
        $upwardRoot = $definition->getBasepath();

        // phpcs:ignore
        $root = realpath($upwardRoot . \DIRECTORY_SEPARATOR . $directory);

        // phpcs:ignore
        if (!$root || !is_dir($root)) {
            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function resolve($definition)
    {
        if (!$definition instanceof Definition) {
            throw new \InvalidArgumentException('$definition must be an instance of ' . Definition::class);
        }

        $directory  = $this->getIterator()->get('directory', $definition);
        $response   = new Stream();
        $upwardRoot = $this->getIterator()->getRootDefinition()->getBasepath();
        // phpcs:ignore
        $root       = realpath($upwardRoot . \DIRECTORY_SEPARATOR . $directory);
        $filename   = $this->getIterator()->get('request.url.pathname');
        // phpcs:ignore
        $path       = realpath($root . $filename);
        if (!$path) {
            // phpcs:ignore
            $path   = realpath(
                rtrim($root, '/') . (str_ends_with(
                    $root,
                    ($p = strtok(ltrim($filename, '/'), '/'))
                ) ? substr($filename, strlen($p) + 1) : $filename
                    )
            );
        }

        // phpcs:ignore
        if (!$path || strpos($path, $root) !== 0 || strpos($path, $upwardRoot) !== 0 || !is_file($path)) {
            $response->setStatusCode(Response::STATUS_CODE_404);
        } else {
            // phpcs:ignore
            $mimeType = (new MimeTypes())->getMimeType(pathinfo($path, \PATHINFO_EXTENSION));

            // phpcs:ignore
            $response->setStream(fopen($path, 'r'));
            $response->getHeaders()->addHeader(new ContentType($mimeType));
            // Enforce best practice and make sure static assets are cacheable
            $response->getHeaders()->addHeaderLine('Cache-Control', 'max-age=31557600');
        }

        return $response;
    }
}
