<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward\Test\Resolver;

use Magento\Upward\Definition;
use Magento\Upward\DefinitionIterator;
use Magento\Upward\Resolver\Proxy;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Zend\Http\Client;
use function BeBat\Verify\verify;

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
                    'proxy' => [
                        'target' => 'google.com',
                    ],
                ]),
                'expected' => true,
            ],
            'Invalid No Proxy' => [
                'definition' => new Definition([]),
                'expected'   => false,
            ],
            'Invalid No Target' => [
                'definition' => new Definition([
                    'proxy' => [
                        'ignoreSSLErrors' => [
                            'inline' => true,
                        ],
                    ],
                ]),
                'expected' => false,
            ],
        ];
    }

    public function testIndicator(): void
    {
        verify($this->resolver->getIndicator())->is()->sameAs('proxy');
    }

    /**
     * @dataProvider isValidDataProvider
     */
    public function testIsValid(Definition $definition, bool $expected): void
    {
        $this->definitionIteratorMock->shouldReceive('getRootDefinition')->andReturn($definition);

        verify($this->resolver->isValid(new Definition([])))->is()->sameAs($expected);
    }

    public function testResolve(): void
    {
        $definition     = new Definition(['resolver' => 'proxy']);
        $rootDefinition = new Definition([
            'proxy' => [
                'target'          => 'https://google.com',
                'ignoreSSLErrors' => true,
            ],
        ]);

        $this->definitionIteratorMock->shouldReceive('get')
            ->with('proxy.target')
            ->andReturn('https://google.com');
        $this->definitionIteratorMock->shouldReceive('getRootDefinition')->andReturn($rootDefinition);
        $this->definitionIteratorMock->shouldReceive('get')
            ->with('proxy.ignoreSSLErrors')
            ->andReturn(true);

        $zendClientMock = Mockery::mock('overload:' . Client::class);
        $zendClientMock->shouldReceive('send');

        $this->resolver->resolve($definition);
    }
}
