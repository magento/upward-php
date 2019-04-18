<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward\Test\Resolver;

use Magento\Upward\Definition;
use Magento\Upward\DefinitionIterator;
use Magento\Upward\Resolver\Url;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use function BeBat\Verify\verify;

class UrlTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var DefinitionIterator|Mockery\MockInterface
     */
    private $definitionIteratorMock;
    /**
     * @var Url
     */
    private $resolver;

    protected function setUp(): void
    {
        $this->resolver               = new Url();
        $this->definitionIteratorMock = Mockery::mock(DefinitionIterator::class);
        $this->definitionIteratorMock->shouldReceive('get')
            ->with(Mockery::type('string'), Mockery::type(Definition::class))
            ->andReturnUsing(function (string $key, Definition $definition) {
                $returnValue = $definition->get($key);

                return $returnValue instanceof Definition ? $returnValue->toArray() : $returnValue;
            });
        $this->resolver->setIterator($this->definitionIteratorMock);
    }

    public function isValidDataProvider()
    {
        return [
            'Valid' => [
                'definition' => new Definition([
                    'baseUrl'  => 'https://upward.test',
                    'query'    => ['foo' => 'bar'],
                    'username' => 'user',
                    'password' => 'pass',
                ]),
                'expected' => true,
            ],
            'Invalid - Wrong Query Type' => [
                'definition' => new Definition([
                    'baseUrl' => false,
                    'query'   => 'foo=bar',
                ]),
                'expected' => false,
            ],
            'Invalid - Password w/o User' => [
                'definition' => new Definition([
                    'baseUrl'  => false,
                    'password' => 'pass',
                ]),
                'expected' => false,
            ],
            'Invalid - Relative Parts w/o Host' => [
                'definition' => new Definition([
                    'baseUrl'  => false,
                    'username' => 'user',
                ]),
                'expected' => false,
            ],
        ];
    }

    public function testIndicator(): void
    {
        verify($this->resolver->getIndicator())->is()->sameAs('baseUrl');
    }

    /**
     * @dataProvider isValidDataProvider
     */
    public function testIsValid(Definition $definition, bool $expected): void
    {
        verify($this->resolver->isValid($definition))->is()->sameAs($expected);
    }

    public function testResolveAbsoluteAllParams(): void
    {
        $definition = new Definition([
            'baseUrl'  => 'https://upward.test',
            'hostname' => 'new.host',
            'protocol' => 'http',
            'pathname' => '/path',
            'search'   => 'foo=bar&dog=cat',
            'query'    => ['foo' => 'baz'],
            'port'     => 8080,
            'username' => 'user',
            'password' => 'pass',
            'hash'     => '#hash',
        ]);

        verify($this->resolver->resolve($definition))->is()->sameAs(
            'http://user:pass@new.host:8080/path?foo=baz&dog=cat#hash'
        );
    }

    public function testResolveAbsoluteWithPathAppend(): void
    {
        $definition = new Definition([
            'baseUrl'  => 'https://upward.test/path/',
            'pathname' => 'append',
        ]);

        verify($this->resolver->resolve($definition))->is()->sameAs(
            'https://upward.test/path/append'
        );
    }

    public function testResolveAbsoluteWithPathSegmentReplace(): void
    {
        $definition = new Definition([
            'baseUrl'  => 'https://upward.test/path/segment',
            'pathname' => 'replace',
        ]);

        verify($this->resolver->resolve($definition))->is()->sameAs(
            'https://upward.test/path/replace'
        );
    }

    public function testResolveRelativeNoParams(): void
    {
        $definition = new Definition([
            'baseUrl'  => false,
            'pathname' => 'no-slash-path',
        ]);

        verify($this->resolver->resolve($definition))->is()->sameAs(
            '/no-slash-path'
        );
    }

    public function testResolveThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$definition must be an instance of Magento\\Upward\\Definition');

        $this->resolver->resolve('Not a Definition');
    }
}
