<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward\Test\Resolver;

use Laminas\Http\Client;
use Laminas\Http\Response;
use Magento\Upward\Definition;
use Magento\Upward\DefinitionIterator;
use Magento\Upward\Resolver\Proxy;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use function BeBat\Verify\verify;

/**
 * @runTestsInSeparateProcesses
 */
class ProxyTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var DefinitionIterator|Mockery\MockInterface
     */
    private $definitionIteratorMock;

    /**
     * @var Proxy
     */
    private $resolver;

    protected function setUp(): void
    {
        $this->resolver               = new Proxy();
        $this->definitionIteratorMock = Mockery::mock(DefinitionIterator::class);
        $this->resolver->setIterator($this->definitionIteratorMock);
    }

    public function isValidDataProvider()
    {
        return [
            'Valid' => [
                'definition' => new Definition([
                    'target' => 'https://google.com',
                ]),
                'expected' => true,
            ],
            'Invalid - No Target' => [
                'definition' => new Definition([]),
                'expected'   => false,
            ],
            'Invalid - Wrong ignoreSSLErrors Type' => [
                'definition' => new Definition([
                    'target'          => 'https://google.com',
                    'ignoreSSLErrors' => [
                        'inline' => 'yes please',
                    ],
                ]),
                'expected' => false,
            ],
        ];
    }

    public function testIndicator(): void
    {
        verify($this->resolver->getIndicator())->is()->sameAs('target');
    }

    /**
     * @dataProvider isValidDataProvider
     */
    public function testIsValid(Definition $definition, bool $expected): void
    {
        $this->definitionIteratorMock->shouldReceive('get')
            ->with('ignoreSSLErrors', $definition)
            ->andReturn($definition->get('ignoreSSLErrors'));

        verify($this->resolver->isValid($definition))->is()->sameAs($expected);
    }

    public function testResolve(): void
    {
        $definition = new Definition(['target' => 'https://google.com', 'ignoreSSLErrors' => true]);

        $this->definitionIteratorMock->shouldReceive('get')
            ->with('target', $definition)
            ->andReturn('https://google.com');
        $this->definitionIteratorMock->shouldReceive('get')
            ->with('ignoreSSLErrors', $definition)
            ->andReturn(true);

        $responseMock   = Mockery::mock(Response::class);
        $zendClientMock = Mockery::mock('overload:' . Client::class);
        $zendClientMock->shouldReceive('send')->andReturn($responseMock);

        $resolverResponse = $this->resolver->resolve($definition);
        verify($resolverResponse)->is()->sameAs($responseMock);
    }

    public function testResolveThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$definition must be an instance of Magento\\Upward\\Definition');

        $this->resolver->resolve('Not a Definition');
    }

    public function testResolveValidateSSL(): void
    {
        $definition = new Definition(['target' => 'https://google.com']);

        $this->definitionIteratorMock->shouldReceive('get')
            ->with('target', $definition)
            ->andReturn('https://google.com');
        $this->definitionIteratorMock->shouldNotReceive('get')
            ->with('ignoreSSLErrors', $definition);

        $responseMock   = Mockery::mock(Response::class);
        $zendClientMock = Mockery::mock('overload:' . Client::class);
        $zendClientMock->shouldReceive('send')->andReturn($responseMock);

        $resolverResponse = $this->resolver->resolve($definition);
        verify($resolverResponse)->is()->sameAs($responseMock);
    }
}
