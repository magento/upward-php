<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward\Test\Resolver;

use Magento\Upward\Context;
use Magento\Upward\Definition;
use Magento\Upward\DefinitionIterator;
use Magento\Upward\Resolver\Conditional;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use function BeBat\Verify\verify;

class ConditionalTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Conditional
     */
    private $resolver;

    protected function setUp(): void
    {
        $this->resolver = new Conditional();
    }

    public function invalidDefinitions(): array
    {
        return [
            'Missing indicator'      => [['default' => 'something']],
            'Missing default'        => [['when' => 'something']],
            'Matcher not definition' => [[
                'when'    => ['some string'],
                'default' => 'some value',
            ]],
            'Matcher missing matches' => [[
                'when' => [[
                    'pattern' => 'some regex',
                    'use'     => 'some value',
                ]],
                'default' => 'some value',
            ]],
            'Matcher matches not string' => [[
                'when' => [[
                    'matches' => ['something'],
                    'pattern' => 'some regex',
                    'use'     => 'some value',
                ]],
                'default' => 'some value',
            ]],
            'Matcher missing pattern' => [[
                'when' => [[
                    'matches' => 'some key',
                    'use'     => 'some value',
                ]],
                'default' => 'some value',
            ]],
            'Matcher pattern not string' => [[
                'when' => [[
                    'matches' => 'some key',
                    'pattern' => 400,
                    'use'     => 'some value',
                ]],
                'default' => 'some value',
            ]],
            'Matcher missing use' => [[
                'when' => [[
                    'matches' => 'some key',
                    'pattern' => 'some regex',
                ]],
                'default' => 'some value',
            ]],
        ];
    }

    public function testEscapeSlashes(): void
    {
        $rootDefinition = new Definition([]);
        $definition     = new Definition([
            'when' => [[
                'matches' => 'context.key',
                'pattern' => 'h(as/se)v(era)l/slash',
                'use'     => 'some resolver',
            ]],
            'default' => 'expected resolver',
        ]);

        $context = new Context([]);

        $iterator = Mockery::mock(DefinitionIterator::class, [$rootDefinition, $context]);
        $this->resolver->setIterator($iterator);

        $iterator->shouldReceive('getContext')
            ->andReturn($context);

        $iterator->shouldReceive('get')
            ->with('context.key')
            ->andReturn('this/has/several/slashes');
        $iterator->shouldReceive('get')
            ->with('use', Mockery::type(Definition::class))
            ->andReturn('Resolved value');

        verify($this->resolver->resolve($definition))->is()->sameAs('Resolved value');
        verify($context->has('$match'))->is()->true();
        verify($context->get('$match.$0'))->is()->sameAs('has/several/slash');
        verify($context->get('$match.$1'))->is()->sameAs('as/se');
        verify($context->get('$match.$2'))->is()->sameAs('era');
    }

    public function testIndicator(): void
    {
        verify($this->resolver->getIndicator())->is()->sameAs('when');
    }

    /**
     * @dataProvider invalidDefinitions
     */
    public function testInvalid(array $data): void
    {
        $definition = new Definition($data);

        verify($this->resolver->isValid($definition))->is()->false();
    }

    public function testIsShorthand(): void
    {
        verify($this->resolver->isShortHand('anything'))->is()->false();
    }

    public function testMatchExceptionFallsThrough(): void
    {
        $definition = new Definition([
            'when' => [[
                'matches' => 'key for undefined value',
                'pattern' => '[a-zA-Z]+',
                'use'     => 'some missing resolver',
            ], [
                'matches' => 'key for alpha value',
                'pattern' => '[0-9]+',
                'use'     => 'another missing resolver',
            ]],
            'default' => 'expected resolver',
        ]);

        $iterator = Mockery::mock(DefinitionIterator::class);
        $this->resolver->setIterator($iterator);

        $iterator->shouldReceive('get')
            ->with('key for undefined value')
            ->andThrow(new \Exception('Undefined value in context'));
        $iterator->shouldReceive('get')
            ->with('key for alpha value')
            ->andReturn('abcdefgHIJKLMNOP');
        $iterator->shouldReceive('get')
            ->with('default', $definition)
            ->andReturn('value for default resolver');

        verify($this->resolver->resolve($definition))->is()->sameAs('value for default resolver');
    }

    public function testResolveThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$definition must be an instance of Magento\\Upward\\Definition');

        $this->resolver->resolve('Not a Definition');
    }

    public function testResolveUsesDefault(): void
    {
        $definition = new Definition([
            'when' => [[
                'matches' => 'key for numeric value',
                'pattern' => '[a-zA-Z]+',
                'use'     => 'some missing resolver',
            ], [
                'matches' => 'key for alpha value',
                'pattern' => '[0-9]+',
                'use'     => 'another missing resolver',
            ]],
            'default' => 'expected resolver',
        ]);

        $iterator = Mockery::mock(DefinitionIterator::class);
        $this->resolver->setIterator($iterator);

        $iterator->shouldReceive('get')
            ->with('key for numeric value')
            ->andReturn('123456');
        $iterator->shouldReceive('get')
            ->with('key for alpha value')
            ->andReturn('abcdefgHIJKLMNOP');
        $iterator->shouldReceive('get')
            ->with('default', $definition)
            ->andReturn('value for default resolver');

        verify($this->resolver->resolve($definition))->is()->sameAs('value for default resolver');
    }

    public function testValidDefinition(): void
    {
        $definition = new Definition([
            'when' => [[
                'matches' => 'some definition key',
                'pattern' => 'some regex',
                'use'     => 'some resolver',
            ], [
                'matches' => 'some other definition key',
                'pattern' => 'yet another regex',
                'use'     => 'probably a different resolver',
            ]],
            'default' => 'some other value',
        ]);

        verify($this->resolver->isValid($definition))->is()->true();
    }

    public function testValidMatch(): void
    {
        $rootDefinition = new Definition([]);
        $definition     = new Definition([
            'when' => [[
                'matches' => 'smog.say-valley-maker.lyrics',
                'pattern' => 'stone',
                'use'     => 'resolver for Say Valley Maker lyrics',
            ], [
                'matches' => 'smog.well.lyrics',
                'pattern' => '(w|y)ell',
                'use'     => 'resolver for Well lyrics',
            ]],
            'default' => 'a default resolver',
        ]);

        $context = new Context([]);

        $iterator = Mockery::mock(DefinitionIterator::class, [$rootDefinition, $context]);
        $this->resolver->setIterator($iterator);

        $iterator->shouldReceive('getContext')
            ->andReturn($context);

        $iterator->shouldReceive('get')
            ->with('smog.say-valley-maker.lyrics')
            ->andReturn('Bury me in wood and I will splinter');
        $iterator->shouldReceive('get')
            ->with('smog.well.lyrics')
            ->andReturn('Everybody has their own thing that they yell into a well');
        $iterator->shouldReceive('get')
            ->with('use', Mockery::type(Definition::class))
            ->andReturn('They say black is all colors at once');

        verify($this->resolver->resolve($definition))->is()->sameAs('They say black is all colors at once');
        verify($context->has('$match'))->is()->true();
        verify($context->get('$match.$0'))->is()->sameAs('yell');
        verify($context->get('$match.$1'))->is()->sameAs('y');
    }
}
