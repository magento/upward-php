<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward\Test;

use Laminas\Http\Response;
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

    public function testClone(): void
    {
        $context    = new Context([]);
        $definition = new Definition([]);
        $original   = new DefinitionIterator($definition, $context);

        verify($original->getContext())->is()->sameAs($context);
        verify($original->getRootDefinition())->is()->sameAs($definition);

        $clone = clone $original;

        verify($clone->getContext())->is()->equalTo($context);
        verify($clone->getContext())->isNot()->sameAs($context);
        verify($clone->getRootDefinition())->is()->sameAs($definition);
    }

    public function testDefinitionLoop(): void
    {
        $context    = new Context([]);
        $definition = new Definition([
            'key1' => 'key2',
            'key2' => 'key3',
            'key3' => 'key1',
        ]);

        $iterator = new DefinitionIterator($definition, $context);

        $resolverFactory = Mockery::mock('alias:' . ResolverFactory::class);

        $resolverFactory->shouldReceive('get')
            ->with(Mockery::any())
            ->andReturnNull();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Definition appears to contain a loop');

        $iterator->get('key1');
    }

    public function testDefinitionNumericKeys(): void
    {
        $context = new Context([]);

        $definition = new Definition([
            'statuses' => [
                'ok',
                'missing',
                'error',
            ],
            'ok'      => 200,
            'missing' => 404,
            'error'   => 500,
        ]);

        $iterator = new DefinitionIterator($definition, $context);

        verify($iterator->get('statuses'))->is()->sameAs([200, 404, 500]);
    }

    public function testDefinitionValueIsBuiltin(): void
    {
        $context    = new Context([]);
        $definition = new Definition(['key' => true]);
        $iterator   = new DefinitionIterator($definition, $context);

        verify($iterator->get('key'))->is()->true();

        // Key has been added to context
        verify($context->get('key'))->is()->true();

        verify($iterator->get('child-key', new Definition(['child-key' => false])))->is()->false();

        // Key was not added to context
        verify($context->has('child-key'))->is()->false();
    }

    public function testIsContextFullyPopulated(): void
    {
        $context = new Context([
            'context-only'       => 'value',
            'static-value'       => 'some value',
            'complex-definition' => ['x', 'y'],
            'simple-definition'  => ['a', 'b'],
            'unfinished-list'    => ['fist'],
            'complete-list'      => ['first', 'second', 'third'],
        ]);

        $definition = new Definition([
            'static-value'       => 'some definition',
            'simple-definition'  => 'some.lookup.value',
            'complex-definition' => [
                'resolver' => 'something',
                'param'    => 'value',
                'key'      => 'another value',
            ],
            'unfinished-list' => ['first', 'second'],
            'complete-list'   => ['first', 'second', 'third'],
        ]);

        $iterator = new DefinitionIterator($definition, $context);

        verify($iterator->isContextFullyPopulated('missing-key'))->is()->false();
        verify($iterator->isContextFullyPopulated('context-only'))->is()->true();
        verify($iterator->isContextFullyPopulated('static-value'))->is()->true();
        verify($iterator->isContextFullyPopulated('simple-definition'))->is()->true();
        verify($iterator->isContextFullyPopulated('complex-definition'))->is()->true();
        verify($iterator->isContextFullyPopulated('unfinished-list'))->is()->false();
        verify($iterator->isContextFullyPopulated('complete-list'))->is()->true();
    }

    public function testIteratingDefinitionTree(): void
    {
        $context    = new Context([]);
        $definition = new Definition([
            'key1' => 'key2',
            'key2' => true,
            'key4' => false,
        ]);

        $iterator = new DefinitionIterator($definition, $context);

        $resolverFactory = Mockery::mock('alias:' . ResolverFactory::class);

        $resolverFactory->shouldReceive('get')
            ->with(Mockery::any())
            ->andReturnNull();

        verify($iterator->get('key1'))->is()->true();

        // Both values added to context
        verify($context->get('key1'))->is()->true();
        verify($context->get('key2'))->is()->true();

        // Child definition is an address for a value in the root definition
        verify($iterator->get('key3', new Definition(['key3' => 'key4'])))->is()->false();

        // Only value from root definition is added to context
        verify($context->has('key3'))->is()->false();
        verify($context->get('key4'))->is()->false();
    }

    public function testLookupInContext(): void
    {
        $context               = new Context(['key' => 'context value']);
        $definition            = new Definition([]);
        $iterator              = new DefinitionIterator($definition, $context);
        $definitionWithSameKey = new Definition(['key' => true]);

        verify($iterator->get('key'))->is()->sameAs('context value');
        verify($iterator->get('key', $definitionWithSameKey))->is()->sameAs(true);
    }

    public function testParentResolver(): void
    {
        $context         = new Context([]);
        $definition      = new Definition(['key' => 'resolver-definition']);
        $childDefinition = new Definition(['child-key' => '200']);
        $iterator        = new DefinitionIterator($definition, $context);
        $resolverFactory = Mockery::mock('alias:' . ResolverFactory::class);
        $mockResolver    = Mockery::mock(ResolverInterface::class);

        $resolverFactory->shouldReceive('get')
            ->with('resolver-definition')
            ->andReturn($mockResolver);

        $mockResolver->shouldReceive('setIterator')
            ->with($iterator);
        $mockResolver->shouldReceive('isValid')
            ->with($definition)
            ->andReturn(true);
        $mockResolver->shouldReceive('resolve')
            ->with('resolver-definition')
            ->andReturn($childDefinition);

        verify($iterator->get('key.child-key'))->is()->sameAs('200');
    }

    public function testParentResolverIsNotArray(): void
    {
        $context         = new Context([]);
        $definition      = new Definition(['key' => 'resolver-definition']);
        $iterator        = new DefinitionIterator($definition, $context);
        $resolverFactory = Mockery::mock('alias:' . ResolverFactory::class);
        $mockResolver    = Mockery::mock(ResolverInterface::class);

        $resolverFactory->shouldReceive('get')
            ->with('resolver-definition')
            ->andReturn($mockResolver);

        $mockResolver->shouldReceive('setIterator')
            ->with($iterator);
        $mockResolver->shouldReceive('isValid')
            ->with($definition)
            ->andReturn(true);
        $mockResolver->shouldReceive('resolve')
            ->with('resolver-definition')
            ->andReturn('200');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Could not get nested value key.child-key from value of type string');

        $iterator->get('key.child-key');
    }

    public function testParentResolverIsResponse(): void
    {
        $context         = new Context([]);
        $definition      = new Definition(['key' => 'resolver-definition']);
        $response        = new Response();
        $iterator        = new DefinitionIterator($definition, $context);
        $resolverFactory = Mockery::mock('alias:' . ResolverFactory::class);
        $mockResolver    = Mockery::mock(ResolverInterface::class);

        $resolverFactory->shouldReceive('get')
            ->with('resolver-definition')
            ->andReturn($mockResolver);

        $mockResolver->shouldReceive('setIterator')
            ->with($iterator);
        $mockResolver->shouldReceive('isValid')
            ->with($definition)
            ->andReturn(true);
        $mockResolver->shouldReceive('resolve')
            ->with('resolver-definition')
            ->andReturn($response);

        verify($iterator->get('key.child-key'))->is()->sameAs($response);
    }

    public function testResolverValueDefinition(): void
    {
        $context         = new Context([]);
        $definition      = new Definition(['key' => 'resolver-definition']);
        $childDefinition = new Definition(['child-key' => true]);
        $iterator        = new DefinitionIterator($definition, $context);
        $resolverFactory = Mockery::mock('alias:' . ResolverFactory::class);
        $mockResolver    = Mockery::mock(ResolverInterface::class);

        $resolverFactory->shouldReceive('get')
            ->with('resolver-definition')
            ->andReturn($mockResolver);

        $mockResolver->shouldReceive('setIterator')
            ->with($iterator);
        $mockResolver->shouldReceive('isValid')
            ->with($definition)
            ->andReturn(true);
        $mockResolver->shouldReceive('resolve')
            ->with('resolver-definition')
            ->andReturn($childDefinition);

        verify($iterator->get('key'))->is()->sameAs(['child-key' => true]);
        verify($context->get('key.child-key'))->is()->true();

        // Intermediate value was not added to context
        verify($context->has('child-key'))->is()->false();
    }

    public function testResolverValueInvalidDefinition(): void
    {
        $context         = new Context([]);
        $definition      = new Definition(['key' => ['child1' => 'value1']]);
        $iterator        = new DefinitionIterator($definition, $context);
        $resolverFactory = Mockery::mock('alias:' . ResolverFactory::class);
        $mockResolver    = Mockery::mock(ResolverInterface::class);

        $resolverFactory->shouldReceive('get')
            ->with(Definition::class)
            ->andReturn($mockResolver);

        $mockResolver->shouldReceive('setIterator')
            ->with($iterator);
        $mockResolver->shouldReceive('isValid')
            ->andReturn(false);
        $mockResolver->shouldNotHaveReceived('resolve');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Definition {"child1":"value1"} is not valid for');

        $iterator->get('key');
    }

    public function testScalarResolverValue(): void
    {
        $context         = new Context([]);
        $definition      = new Definition(['key' => 'resolver-definition']);
        $childDefinition = new Definition(['child-key' => 'resolver-for-child']);
        $iterator        = new DefinitionIterator($definition, $context);
        $resolverFactory = Mockery::mock('alias:' . ResolverFactory::class);
        $mockResolver    = Mockery::mock(ResolverInterface::class);

        $resolverFactory->shouldReceive('get')
            ->with('resolver-definition')
            ->andReturn($mockResolver);
        $resolverFactory->shouldReceive('get')
            ->with('resolver-for-child')
            ->andReturn($mockResolver);

        $mockResolver->shouldReceive('setIterator')
            ->with($iterator);
        $mockResolver->shouldReceive('resolve')
            ->with('resolver-definition')
            ->andReturn('resolver value');
        $mockResolver->shouldReceive('resolve')
            ->with('resolver-for-child')
            ->andReturn('child resolver value');

        verify($iterator->get('key'))->is()->sameAs('resolver value');
        verify($context->get('key'))->is()->sameAs('resolver value');

        verify($iterator->get('child-key', $childDefinition))->is()->sameAs('child resolver value');
        verify($context->has('child-key'))->is()->false();
    }
}
