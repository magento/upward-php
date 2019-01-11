<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward\Template\Mustache;

use Magento\Upward\Template\TemplateInterface;

class Engine implements TemplateInterface
{
    /**
     * @var \Mustache_Engine
     */
    private $mustacheEngine;

    public function __construct(string $basePath)
    {
        $this->mustacheEngine = new \Mustache_Engine([
            'partials_loader' => new FileLoader($basePath, ['extension' => 'mst']),
        ]);
    }

    public function render(string $template, array $data = []): string
    {
        return $this->mustacheEngine->render($template, $data);
    }
}
