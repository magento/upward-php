<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward\Test\Template;

use Magento\Upward\Template\Mustache\Engine;
use Magento\Upward\Template\TemplateFactory;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use function BeBat\Verify\verify;

class TemplateFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGetWithEngine(): void
    {
        verify(TemplateFactory::get(__DIR__, 'mustache'))->is()->instanceOf(Engine::class);
    }

    public function testGetWithInvalidEngine(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('sideburns could not be found or does not implement TemplateInterface');

        TemplateFactory::get(__DIR__, 'sideburns');
    }

    public function testGetWithoutEngine(): void
    {
        verify(TemplateFactory::get(__DIR__, null))->is()->instanceOf(Engine::class);
    }
}
