<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward\Resolver;

use Magento\Upward\Definition;
use Mimey\MimeTypes;
use Zend\Http\Header\ContentType;
use Zend\Http\Response;
use Zend\Http\Response\Stream;

class Directory extends AbstractResolver
{
    /**
     * {@inheritdoc}
     */
    public function getIndicator(): string
    {
        return 'directory';
    }

    /**
     * {@inheritdoc}
     */
    public function isValid(Definition $definition): bool
    {
        if (!$definition->has($this->getIndicator())) {
            return false;
        }

        $directory  = $this->getIterator()->get('directory', $definition);
        $upwardRoot = $definition->getBasepath();

        $root = realpath($upwardRoot . \DIRECTORY_SEPARATOR . $directory);

        if (!$root || !is_dir($root)) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve($definition)
    {
        if (!$definition instanceof Definition) {
            throw new \InvalidArgumentException('$definition must be an instance of ' . Definition::class);
        }

        $directory  = $this->getIterator()->get('directory', $definition);
        $headers    = $this->getIterator()->get('headers', $definition);
        $response   = new Stream();
        $upwardRoot = $this->getIterator()->getRootDefinition()->getBasepath();
        $root       = realpath($upwardRoot . \DIRECTORY_SEPARATOR . $directory);
        $filename   = $this->getIterator()->get('request.url.pathname');
        $path       = realpath($root . $filename);

        if (!$path || strpos($path, $root) !== 0 || !is_file($path)) {
            $response->setStatusCode(Response::STATUS_CODE_404);
        } else {
            $mimeType = (new MimeTypes())->getMimeType(pathinfo($path, PATHINFO_EXTENSION));

            $response->setStream(fopen($path, 'r'));
            $response->getHeaders()->addHeader(new ContentType($mimeType));
            $response->getHeaders()->addHeaders($headers);
        }

        return $response;
    }
}
