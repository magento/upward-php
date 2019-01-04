<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward\Test\Resolver;

use Magento\Upward\Definition;
use Magento\Upward\DefinitionIterator;
use Magento\Upward\Resolver\Template;
use Mockery;
use PHPUnit\Framework\TestCase;
use function BeBat\Verify\verify;

class TemplateTest extends TestCase
{
    /**
     * @var DefinitionIterator|Mockery\MockInterface
     */
    private $definitionIteratorMock;
    /**
     * @var Template
     */
    private $resolver;

    protected function setUp(): void
    {
        $this->resolver               = new Template();
        $this->definitionIteratorMock = Mockery::mock(DefinitionIterator::class);
        $this->resolver->setIterator($this->definitionIteratorMock);
    }

    public function isValidDataProvider()
    {
        return [
            'Valid With Engine' => [
                'definition' => new Definition([
                    'engine'   => 'mustache',
                    'provide'  => true,
                    'template' => true,
                ]),
                'expected' => true,
            ],
            'Valid Without Engine' => [
                'definition' => new Definition([
                    'template' => true,
                    'provide'  => true,
                ]),
                'expected' => true,
            ],
            'Invalid Unsupported Engine' => [
                'definition' => new Definition([
                    'engine'   => 'sideburns',
                    'provide'  => true,
                    'template' => true,
                ]),
                'expected' => false,
            ],
            'Invalid No Template' => [
                'definition' => new Definition([
                    'engine'  => 'mustache',
                    'provide' => true,
                ]),
                'expected' => false,
            ],
            'Invalid No Provide' => [
                'definition' => new Definition([
                    'engine'   => 'mustache',
                    'template' => true,
                ]),
                'expected' => false,
            ],
        ];
    }

    public function testIndicator(): void
    {
        verify($this->resolver->getIndicator())->is()->sameAs('template');
    }

    /**
     * @dataProvider isValidDataProvider
     */
    public function testIsValid(Definition $definition, bool $expected): void
    {
        $this->definitionIteratorMock
            ->shouldReceive('get')
            ->with('engine', $definition)
            ->andReturn($definition->get('engine'));
        verify($this->resolver->isValid($definition))->is()->sameAs($expected);
    }
}
