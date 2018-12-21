<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward\Test;

use Magento\Upward\Definition;
use PHPUnit\Framework\TestCase;
use function BeBat\Verify\verify;

class DefinitionTest extends TestCase
{
    /**
     * @var Definition
     */
    private $definition;

    protected function setUp(): void
    {
        $this->definition = new Definition([
            'key0' => [
                'key00' => [
                    'key000' => 'value',
                ],
            ],
        ]);

        $this->definition->setBasepath(__DIR__);
    }

    public function testBasepathInheritance(): void
    {
        $this->assertionsForBasepath($this->definition->get('key0'));
        $this->assertionsForBasepath($this->definition->get('key0.key00'));
        $this->assertionsForBasepath($this->definition->get('key0')->get('key00'));
    }

    public function testTreeAddress(): void
    {
        verify($this->definition->get('key0'))->is()->instanceOf(Definition::class);
        verify($this->definition->get('key0')->getTreeAddress())->is()->sameAs('key0');

        verify($this->definition->get('key0.key00'))->is()->instanceOf(Definition::class);
        verify($this->definition->get('key0.key00')->getTreeAddress())->is()->sameAs('key0.key00');

        verify($this->definition->get('key0')->get('key00'))->is()->instanceOf(Definition::class);
        verify($this->definition->get('key0')->get('key00')->getTreeAddress())->is()->sameAs('key0.key00');
    }

    private function assertionsForBasepath($subject): void
    {
        verify($subject)->is()->instanceOf(Definition::class);
        verify($subject->getBasepath())->is()->sameAs(__DIR__);
    }
}
