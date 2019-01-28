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
use Magento\Upward\Template\TemplateFactory;
use Magento\Upward\Template\TemplateInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use function BeBat\Verify\verify;

/**
 * @runTestsInSeparateProcesses
 */
class TemplateTest extends TestCase
{
    use MockeryPHPUnitIntegration;

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
        verify($this->resolver->getIndicator())->is()->sameAs('engine');
    }

    /**
     * @dataProvider isValidDataProvider
     */
    public function testIsValid(Definition $definition, bool $expected): void
    {
        $templateFactoryMock = Mockery::mock('alias:' . TemplateFactory::class);
        $templateFactoryMock->shouldReceive('get');
        $this->definitionIteratorMock->shouldReceive('get')
            ->with('engine', $definition)
            ->andReturn($definition->get('engine'));
        verify($this->resolver->isValid($definition))->is()->sameAs($expected);
    }

    public function testIsValidWithException(): void
    {
        $definition = new Definition([
            'engine'   => 'sideburns',
            'provide'  => true,
            'template' => true,
        ]);
        $templateFactoryMock = Mockery::mock('alias:' . TemplateFactory::class);
        $templateFactoryMock->shouldReceive('get')->andThrow(\InvalidArgumentException::class);
        $this->definitionIteratorMock->shouldReceive('get')
            ->with('engine', $definition)
            ->andReturn($definition->get('engine'));
        verify($this->resolver->isValid($definition))->is()->false();
    }

    public function testResolveWithResolvedValue(): void
    {
        $templateFactoryMock = Mockery::mock('alias:' . TemplateFactory::class);
        $engineMock          = Mockery::mock(TemplateInterface::class);

        $definition = new Definition([
            'engine'   => 'mustache',
            'template' => 'My Template',
            'provide'  => [
                'inlineKey' => 'some.lookup',
            ],
        ]);

        $this->definitionIteratorMock->shouldReceive('get')
            ->with('engine', $definition)
            ->andReturn($definition->get('engine'));
        $this->definitionIteratorMock->shouldReceive('get')
            ->with('template', $definition)
            ->andReturn($definition->get('template'));
        $this->definitionIteratorMock->shouldReceive('get')
            ->with('provide', $definition)
            ->andReturn(['inlineKey' => 'inlineValue']);
        $this->definitionIteratorMock->shouldReceive('get')
            ->with('some.lookup')
            ->andReturn('inlineValue');

        $this->definitionIteratorMock->shouldReceive('getRootDefinition')
            ->andReturn($definition);

        $templateFactoryMock->shouldReceive('get')
            ->with($definition->getBasepath(), 'mustache')
            ->andReturn($engineMock);

        $engineMock->shouldReceive('render')
            ->with('My Template', ['inlineKey' => 'inlineValue'])
            ->andReturn('My Rendered Template');

        verify($this->resolver->resolve($definition))->is()->sameAs('My Rendered Template');
    }

    public function testResolveWithRootValue(): void
    {
        $templateFactoryMock = Mockery::mock('alias:' . TemplateFactory::class);
        $engineMock          = Mockery::mock(TemplateInterface::class);

        $definition = new Definition([
            'template' => 'My Template',
            'provide'  => [
                'rootValue',
            ],
        ]);

        $this->definitionIteratorMock->shouldReceive('get')
            ->with('engine', $definition)
            ->andReturn($definition->get('engine'));
        $this->definitionIteratorMock->shouldReceive('get')
            ->with('template', $definition)
            ->andReturn($definition->get('template'));
        $this->definitionIteratorMock->shouldReceive('get')
            ->with('rootValue')
            ->andReturn('resolvedRootValue');

        $this->definitionIteratorMock->shouldReceive('getRootDefinition')
            ->andReturn($definition);

        $templateFactoryMock->shouldReceive('get')
            ->with($definition->getBasepath(), null)
            ->andReturn($engineMock);

        $engineMock->shouldReceive('render')
            ->with('My Template', ['rootValue' => 'resolvedRootValue'])
            ->andReturn('My Rendered Template');

        verify($this->resolver->resolve($definition))->is()->sameAs('My Rendered Template');
    }
}
