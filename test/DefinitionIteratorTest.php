<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward\Test;

use Magento\Upward\Context;
use Magento\Upward\Definition;
use Magento\Upward\DefinitionIterator;
use Magento\Upward\Resolver\ResolverInterface;
use Magento\Upward\ResolverFactory;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use function BeBat\Verify\verify;

class DefinitionIteratorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var DefinitionIterator
     */
    private $iterator;

    /**
     * @var Context|Mockery\MockInterface
     */
    private $mockContext;

    /**
     * @var Definition|Mockery\MockInterface
     */
    private $mockDefinition;

    /**
     * @var ResolverFactory|Mockery\MockInterface
     */
    private $mockResolverFactory;

    protected function setUp(): void
    {
        $this->mockContext         = Mockery::mock(Context::class);
        $this->mockDefinition      = Mockery::mock(Definition::class);
        $this->mockResolverFactory = Mockery::mock('alias:' . ResolverFactory::class);

        $this->mockContext->shouldReceive('has')
            ->with(Mockery::any())
            ->andReturn(false)
            ->byDefault();
        $this->mockContext->shouldReceive('isBuiltinValue')
            ->with(Mockery::any())
            ->andReturn(false)
            ->byDefault();
        $this->mockContext->shouldNotReceive('get')
            ->byDefault();

        $this->iterator = new DefinitionIterator($this->mockDefinition, $this->mockContext);
    }

    public function testDefinitionIsBuiltIn(): void
    {
        $this->mockDefinition->shouldReceive('get')
            ->with('lookup value')
            ->andReturn('definition value');

        $this->mockContext->shouldReceive('isBuiltinValue')
            ->with('definition value')
            ->andReturn(true);
        $this->mockContext->shouldReceive('set')
            ->with('lookup value', 'definition value')
            ->once();

        verify($this->iterator->get('lookup value'))->is()->sameAs('definition value');
    }

    public function testDefinitionLoop(): void
    {
        $this->mockDefinition->shouldReceive('get')
            ->with('lookup value')
            ->andReturn('lookup value');

        $this->mockResolverFactory->shouldReceive('get')
            ->with('lookup value')
            ->andReturnNull();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Definition appears to contain a loop');

        $this->iterator->get('lookup value');
    }

    public function testGetFromContext(): void
    {
        $this->mockContext->shouldReceive('has')
            ->with('lookup value')
            ->andReturn(true);
        $this->mockContext->shouldReceive('get')
            ->with('lookup value')
            ->andReturn('context value');

        verify($this->iterator->get('lookup value'))->is()->sameAs('context value');
    }

    public function testGetFromResolver(): void
    {
        $mockResolver = Mockery::mock(ResolverInterface::class);

        $this->mockDefinition->shouldReceive('get')
            ->globally()->ordered()
            ->with('lookup value')
            ->andReturn('definition value');

        $this->mockResolverFactory->shouldReceive('get')
            ->globally()->ordered()
            ->with('definition value')
            ->andReturn($mockResolver);

        $mockResolver->shouldReceive('setIterator')
            ->with($this->iterator)
            ->ordered();
        $mockResolver->shouldReceive('resolve')
            ->with('definition value')
            ->ordered()
            ->andReturn('resolver value');

        $this->mockContext->shouldReceive('set')
            ->with('lookup value', 'resolver value');

        verify($this->iterator->get('lookup value'))->is()->sameAs('resolver value');
    }

    public function testGetResovlerDefinition(): void
    {
        $mockResolver    = Mockery::mock(ResolverInterface::class);
        $childDefinition = new Definition([
            'key1' => 'value',
            'key2' => 'value',
        ]);

        $resolvedValues = [
            'key1' => 'context value 1',
            'key2' => 'context value 2',
        ];

        $this->mockDefinition->shouldReceive('get')
            ->globally()->ordered()
            ->with('lookup value')
            ->andReturn('definition value');

        $this->mockResolverFactory->shouldReceive('get')
            ->globally()->ordered()
            ->with('definition value')
            ->andReturn($mockResolver);

        $mockResolver->shouldReceive('setIterator')
            ->with($this->iterator)
            ->ordered();
        $mockResolver->shouldReceive('resolve')
            ->with('definition value')
            ->ordered()
            ->andReturn($childDefinition);

        $this->mockContext->shouldReceive('has')
            ->with('key1')
            ->andReturn(true);
        $this->mockContext->shouldReceive('has')
            ->with('key2')
            ->andReturn(true);
        $this->mockContext->shouldReceive('get')
            ->with('key1')
            ->andReturn('context value 1');
        $this->mockContext->shouldReceive('get')
            ->with('key2')
            ->andReturn('context value 2');

        $this->mockContext->shouldReceive('set')
            ->once()
            ->with('lookup value', $resolvedValues);

        verify($this->iterator->get('lookup value'))->is()->sameAs($resolvedValues);
    }

    public function testGetSecondaryLookup(): void
    {
        $this->mockDefinition->shouldReceive('get')
            ->globally()->ordered()
            ->with('lookup value')
            ->andReturn('definition value');

        $this->mockResolverFactory->shouldReceive('get')
            ->globally()->ordered()
            ->with('definition value')
            ->andReturnNull();

        $this->mockContext->shouldReceive('has')
            ->globally()->ordered()
            ->with('definition value')
            ->andReturn(true);
        $this->mockContext->shouldReceive('get')
            ->globally()->ordered()
            ->with('definition value')
            ->andReturn('context value');

        verify($this->iterator->get('lookup value'))->is()->sameAs('context value');
    }

    public function testNoDefinition(): void
    {
        $this->mockDefinition->shouldReceive('get')
            ->with('lookup value')
            ->andReturnNull();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No definition for lookup value');

        $this->iterator->get('lookup value');
    }
}
