<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward\Test\Resolver;

use Laminas\Http\Response;
use Magento\Upward\Definition;
use Magento\Upward\DefinitionIterator;
use Magento\Upward\Resolver\Directory;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use function BeBat\Verify\verify;

class DirectoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var DefinitionIterator|Mockery\MockInterface
     */
    private $mockIterator;

    /**
     * @var Directory
     */
    private $resolver;

    protected function setUp(): void
    {
        $this->mockIterator = Mockery::mock(DefinitionIterator::class);
        $this->resolver     = new Directory();

        $this->resolver->setIterator($this->mockIterator);

        $this->mockIterator->shouldReceive('getRootDefinition->getBasepath')
            ->andReturn(__DIR__);
        $this->mockIterator->shouldReceive('get')
            ->with(Mockery::type('string'), Mockery::type(Definition::class))
            ->andReturnUsing(function (string $key, Definition $definition) {
                return $definition->get($key);
            });
    }

    public function dataProviderFor404(): array
    {
        return [
            'Missing File'      => ['directory' => './_data',          'filename' => '/not-real-file.txt'],
            'Missing Directory' => ['directory' => './not-real',       'filename' => '/sample.txt'],
            'Not Directory'     => ['directory' => basename(__FILE__), 'filename' => '/sample.txt'],
            'Outside Directory' => ['directory' => '../_data',          'filename' => '/sample.txt'],
            'File is Root'      => ['directory' => './_data',          'filename' => '/'],
            'File is Directory' => ['directory' => './_data',          'filename' => '/test'],
        ];
    }

    public function testIndicator(): void
    {
        verify($this->resolver->getIndicator())->is()->sameAs('directory');
    }

    public function testIsShorthand(): void
    {
        verify($this->resolver->isShortHand('anything'))->is()->false();
    }

    public function testIsValid(): void
    {
        $validDefinition  = new Definition(['directory' => '_data']);
        $missingDirectory = new Definition(['somekey' => 'somevalue']);
        $notADirectory    = new Definition(['directory' => basename(__FILE__)]);
        $doesNotExist     = new Definition(['directory' => 'not_real']);

        $validDefinition->setBasepath(__DIR__);
        $missingDirectory->setBasepath(__DIR__);
        $notADirectory->setBasepath(__DIR__);
        $doesNotExist->setBasepath(__DIR__);

        verify($this->resolver->isValid($validDefinition))->is()->true();
        verify($this->resolver->isValid($missingDirectory))->is()->false();
        verify($this->resolver->isValid($notADirectory))->is()->false();
        verify($this->resolver->isValid($doesNotExist))->is()->false();
    }

    public function testResolve(): void
    {
        $definition = new Definition(['directory' => './_data']);

        $this->mockIterator->shouldReceive('get')
            ->with('request.url.pathname')
            ->andReturn('/sample.txt');

        $result = $this->resolver->resolve($definition);

        verify($result)->is()->instanceOf(Response::class);
        verify($result->getStatusCode())->is()->sameAs(200);
        verify($result->getHeaders()->get('Content-Type')->getFieldValue())->is()->sameAs('text/plain');
        verify($result->getHeaders()->get('Cache-Control')->getFieldValue())->is()->sameAs('max-age=31557600');
        verify($result->getBody())->is()->equalToFile(__DIR__ . '/_data/sample.txt');
    }

    /**
     * @dataProvider dataProviderFor404
     */
    public function testResolve404(string $directory, string $filename): void
    {
        $definition = new Definition(compact('directory'));

        $this->mockIterator->shouldReceive('get')
            ->with('request.url.pathname')
            ->andReturn($filename);

        $result = $this->resolver->resolve($definition);

        verify($result)->is()->instanceOf(Response::class);
        verify($result->getStatusCode())->is()->sameAs(404);
        verify($result->getBody())->is()->empty();
    }

    public function testResolveException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$definition must be an instance of Magento\\Upward\\Definition');

        $this->resolver->resolve('_data');
    }
}
