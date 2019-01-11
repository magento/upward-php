<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward\Test\Template\Mustache;

use Magento\Upward\Template\Mustache\Engine;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use function BeBat\Verify\verify;

class EngineTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testRender(): void
    {
        $mustacheEngine = new Engine(__DIR__ . '/../../_data');
        verify($mustacheEngine->render('{{> templates/template}}', ['variable' => 'custom variable']))
            ->will()->sameAs('<h1>A Mustache Template with a custom variable</h1>');
    }
}
