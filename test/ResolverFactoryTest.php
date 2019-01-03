<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward\Test;

use Magento\Upward\Definition;
use Magento\Upward\Resolver\ResolverInterface;
use Magento\Upward\ResolverFactory;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use function BeBat\Verify\verify;

/**
 * @runTestsInSeparateProcesses
 */
class ResolverFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGetForDefinitionWithInference(): void
    {
        $resolver     = Mockery::mock(ResolverInterface::class);
        $defininition = new Definition(['someKey' => 'someValue']);

        $resolver->shouldReceive('getIndicator')
            ->andReturn('someKey');
        $resolver->shouldReceive('isValid')
            ->with($defininition)
            ->andReturn(true);

        // Add a "dummy" test resolver type
        ResolverFactory::addResolverClass('test', 'stdClass');
        ResolverFactory::addResolver('test', $resolver);

        verify(ResolverFactory::get($defininition))->is()->sameAs($resolver);
    }

    public function testGetForDefinitionWithResolver(): void
    {
        $resolver     = Mockery::mock(ResolverInterface::class);
        $defininition = new Definition(['resolver' => 'test']);

        $resolver->shouldReceive('isValid')
            ->with($defininition)
            ->andReturn(true);

        ResolverFactory::addResolver('test', $resolver);

        verify(ResolverFactory::get($defininition))->is()->sameAs($resolver);
    }

    public function testGetForScalarNoResolver(): void
    {
        $resolver = Mockery::mock(ResolverInterface::class);

        $resolver->shouldReceive('isShorthand')
            ->with('scalar value')
            ->andReturn(false);

        ResolverFactory::addResolver('test', $resolver);

        verify(ResolverFactory::get('scalar value'))->is()->null();
    }

    public function testGetForScalarShorthand(): void
    {
        $resolver = Mockery::mock(ResolverInterface::class);

        $resolver->shouldReceive('isShorthand')
            ->with('scalar value')
            ->andReturn(true);

        // Add a "dummy" test resolver that points to PHP stdClass
        ResolverFactory::addResolverClass('test', 'stdClass');
        ResolverFactory::addResolver('test', $resolver);

        verify(ResolverFactory::get('scalar value'))->is()->sameAs($resolver);
    }

    public function testNoResolverClassDefined(): void
    {
        $defininition = new Definition(['resolver' => 'no-resolver']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No resolver class defined for resolver no-resolver');

        ResolverFactory::get($defininition);
    }

    public function testNoResovlerInferred(): void
    {
        $defininition = new Definition([]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No resolver found for definition');

        ResolverFactory::get($defininition);
    }
}
