<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward\Test\Template;

use Magento\Upward\Template\Mustache;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use function BeBat\Verify\verify;

class MustacheTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testRender(): void
    {
        $mustacheEngine = new Mustache(__DIR__ . '/../_data');
        verify($mustacheEngine->render('{{> templates/template}}', ['variable' => 'custom variable']))
            ->will()->contain('<h1>A Mustache Template with a custom variable</h1>');
    }
}
