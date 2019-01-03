<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward\Template;

class Mustache implements TemplateInterface
{
    /**
     * @var \Mustache_Engine
     */
    private $mustacheEngine;

    public function __construct()
    {
        $this->mustacheEngine = new \Mustache_Engine([
            'partials_loader' => new \Mustache_Loader_FilesystemLoader(
                __DIR__ . '/../../pwa/templates',
                ['extension' => 'mst']
            )
        ]);
    }

    public function render(string $template, array $data = []): string
    {
        return $this->mustacheEngine->render($template, $data);
    }
}
