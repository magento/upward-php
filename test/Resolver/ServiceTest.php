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
use Magento\Upward\Resolver\Service;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use function BeBat\Verify\verify;

/**
 * @runTestsInSeparateProcesses
 */
class ServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var DefinitionIterator|Mockery\MockInterface
     */
    private $definitionIteratorMock;

    /**
     * @var Service
     */
    private $resolver;

    protected function setUp(): void
    {
        $this->resolver               = new Service();
        $this->definitionIteratorMock = Mockery::mock(DefinitionIterator::class);
        $this->resolver->setIterator($this->definitionIteratorMock);
    }

    /**
     * Data provider to verify backwards compatibility with previous indicator.
     *
     * @return string[]
     */
    public function indicatorDataProvider()
    {
        return [['url'], ['endpoint']];
    }

    public function testIndicator(): void
    {
        verify($this->resolver->getIndicator())->is()->sameAs('endpoint');
    }

    /**
     * @dataProvider indicatorDataProvider
     */
    public function testIsValid(string $indicator): void
    {
        $validDefinition           = new Definition([$indicator => '/graphql', 'query' => 'gql']);
        $validWithMethodDefinition = new Definition([$indicator => '/graphql', 'query' => 'gql', 'method' => 'GET']);
        $invalidNoURL              = new Definition(['query' => 'gql']);
        $invalidNoQuery            = new Definition([$indicator => '/graphql']);
        $invalidUnsupportedMethod  = new Definition([$indicator => '/graphql', 'query' => 'gql', 'method' => 'PUT']);

        $this->definitionIteratorMock->shouldReceive('get')
            ->twice()
            ->with('method', Mockery::type(Definition::class))
            ->andReturnUsing(function (string $key, Definition $definition) {
                return $definition->get($key);
            });

        verify($this->resolver->isValid($validDefinition))->is()->true();
        verify($this->resolver->isValid($validWithMethodDefinition))->is()->true();
        verify($this->resolver->isValid($invalidNoURL))->is()->false();
        verify($this->resolver->isValid($invalidNoQuery))->is()->false();
        verify($this->resolver->isValid($invalidUnsupportedMethod))->is()->false();
    }

    /**
     * @dataProvider indicatorDataProvider
     */
    public function testResolve(string $indicator): void
    {
        $definition            = new Definition([$indicator => '/graphql', 'query' => 'gql']);
        $expectedRequestBody   = json_encode(['query' => 'gql', 'variables' => []]);
        $expectedResponseArray = ['data' => ['key' => 'value']];

        $this->definitionIteratorMock->shouldReceive('get')
            ->twice()
            ->with(Mockery::type('string'), Mockery::type(Definition::class))
            ->andReturnUsing(function (string $key, Definition $definition) {
                return $definition->get($key);
            });

        $responseMock = Mockery::mock(Response::class);
        $responseMock->shouldReceive('getBody')->andReturn('{"data":{"key":"value"}}');

        $zendClientMock = Mockery::mock('overload:' . Client::class);
        $zendClientMock->shouldReceive('setMethod')->with('POST');
        $zendClientMock->shouldReceive('setHeaders')->with(['Content-type' => 'application/json']);
        $zendClientMock->shouldReceive('setRawBody')->with($expectedRequestBody);
        $zendClientMock->shouldReceive('send')->andReturn($responseMock);

        verify($this->resolver->resolve($definition))->is()->sameAs($expectedResponseArray);
    }

    public function testResolveThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$definition must be an instance of Magento\\Upward\\Definition');

        $this->resolver->resolve('Not a Definition');
    }

    /**
     * @dataProvider indicatorDataProvider
     */
    public function testResolveWithConfiguration(string $indicator): void
    {
        $definition = new Definition([
            $indicator  => '/graphql',
            'query'     => 'gql',
            'variables' => [
                'var1' => 'var1Value',
            ],
            'headers' => [
                'header' => 'headerValue',
            ],
            'method' => 'GET',
        ]);
        $expectedRequestBody   = ['query' => 'gql', 'variables' => ['var1' => 'var1Value']];
        $expectedResponseArray = ['data' => ['key' => 'value']];

        $this->definitionIteratorMock->shouldReceive('get')
            ->times(5)
            ->with(Mockery::type('string'), Mockery::type(Definition::class))
            ->andReturnUsing(function (string $key, Definition $definition) {
                $returnValue = $definition->get($key);

                return $returnValue instanceof Definition ? $returnValue->toArray() : $returnValue;
            });

        $responseMock = Mockery::mock(Response::class);
        $responseMock->shouldReceive('getBody')->andReturn('{"data":{"key":"value"}}');

        $zendClientMock = Mockery::mock('overload:' . Client::class);
        $zendClientMock->shouldReceive('setMethod')->with('GET');
        $zendClientMock->shouldReceive('setHeaders')
            ->with(['header' => 'headerValue']);
        $zendClientMock->shouldReceive('setParameterGet')->with($expectedRequestBody);
        $zendClientMock->shouldReceive('send')->andReturn($responseMock);

        verify($this->resolver->resolve($definition))->is()->sameAs($expectedResponseArray);
    }
}
