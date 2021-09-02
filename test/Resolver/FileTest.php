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
use Magento\Upward\Resolver\File;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use function BeBat\Verify\verify;

class FileTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var DefinitionIterator|Mockery\MockInterface
     */
    private $mockIterator;

    /**
     * @var File
     */
    private $resolver;

    protected function setUp(): void
    {
        $this->mockIterator = Mockery::mock(DefinitionIterator::class);
        $this->resolver     = new File();

        $this->resolver->setIterator($this->mockIterator);

        $this->mockIterator->shouldReceive('get')
            ->with(Mockery::type('string'), Mockery::type(Definition::class))
            ->andReturnUsing(function (string $key, Definition $definition) {
                return $definition->get($key);
            });

        $this->mockIterator->shouldReceive('getRootDefinition->getBasePath')
            ->andReturn(__DIR__);
    }

    public function testIndicator(): void
    {
        verify($this->resolver->getIndicator())->is()->sameAs('file');
    }

    public function testIsShorthand(): void
    {
        verify($this->resolver->isShortHand('/absolute/path/name.txt'))->is()->true();
        verify($this->resolver->isShortHand('./relative/path/name.pdf'))->is()->true();
        verify($this->resolver->isShortHand('../../path/up/the/tree.jpg'))->is()->true();
        verify($this->resolver->isShortHand('file://uri/file/path.md'))->is()->true();
        verify($this->resolver->isShortHand('C:\\Even\\A\\Windows\\Path.exe'))->is()->true();

        verify($this->resolver->isShortHand('some/different/type/of/path.zip'))->is()->false();
    }

    public function testIsValid(): void
    {
        $simpleDefinition   = new Definition(['file' => '/some/file/path.gif']);
        $completeDefinition = new Definition([
            'file'     => '/this/is/a/path.indd',
            'encoding' => 'binary',
            'parse'    => 'text',
        ]);
        $missingFile     = new Definition(['some_key' => 'some other value']);
        $invalidEncoding = new Definition([
            'file'     => '/something/really/old.ini',
            'encoding' => 'EBCDIC',
        ]);
        $invalidParse = new Definition([
            'file'  => '/cannot/parse/this.html',
            'parse' => 'html',
        ]);

        verify($this->resolver->isValid($simpleDefinition))->is()->true();
        verify($this->resolver->isValid($completeDefinition))->is()->true();

        verify($this->resolver->isValid($missingFile))->is()->false();
        verify($this->resolver->isValid($invalidEncoding))->is()->false();
        verify($this->resolver->isValid($invalidParse))->is()->false();
    }

    public function testJsonException(): void
    {
        $this->expectException(\RuntimeException::class);
        // Omit portion of the error message that comes from buitlin PHP value
        $this->expectExceptionMessage('Failed to parse invalid.json: ');

        $this->resolver->resolve('./_data/invalid.json');
    }

    public function testResolve(): void
    {
        $definition = new Definition(['file' => './_data/sample.txt']);

        verify($this->resolver->resolve('./_data/sample.txt'))->is()->sameAs("This is a sample file.\n");
        verify($this->resolver->resolve($definition))->is()->sameAs("This is a sample file.\n");
    }

    public function testResolve404(): void
    {
        $notFoundResult = $this->resolver->resolve('./_data/notfound.txt');
        $pathResult     = $this->resolver->resolve('../_data/sample.txt');

        verify($notFoundResult)->is()->instanceOf(Response::class);
        verify($notFoundResult->getStatusCode())->is()->sameAs(404);
        verify($pathResult)->is()->instanceOf(Response::class);
        verify($pathResult->getStatusCode())->is()->sameAs(404);
    }

    public function testResolveJson(): void
    {
        verify($this->resolver->resolve('./_data/sample.json'))->is()->sameAs(['json' => true]);
    }

    public function testyResolveWithoutParse(): void
    {
        $definition = new Definition(['file' => './_data/sample.json', 'parse' => 'text']);

        verify($this->resolver->resolve($definition))->is()->sameAs('{"json": true}' . \PHP_EOL);
    }
}
