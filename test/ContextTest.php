<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward\Test;

use Laminas\Http\PhpEnvironment\Request;
use Magento\Upward\Context;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use function BeBat\Verify\verify;

class ContextTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function builtinDataProvider(): array
    {
        return [
            ['GET'],
            ['POST'],
            ['mustache'],
            ['text/plain'],
            ['utf8'],
            ['100'],
            ['599'],
        ];
    }

    public function testClone(): void
    {
        $original = new Context([]);

        $original->set('delete-me1', 'some value', false);
        $original->set('delete-me2', 'some other value', false);
        $original->set('keep-me', 'some permanent value');

        verify($original->has('delete-me1'))->is()->true();
        verify($original->has('delete-me2'))->is()->true();
        verify($original->has('keep-me'))->is()->true();

        $clone = clone $original;

        verify($clone->has('delete-me1'))->is()->false();
        verify($clone->has('delete-me2'))->is()->false();
        verify($clone->has('keep-me'))->is()->true();
    }

    public function testFromRequest(): void
    {
        $request = Mockery::mock(Request::class);

        $request->shouldReceive('getHeaders->toArray')
            ->andReturn(['Request-Header' => 'header value']);
        $request->shouldReceive('getQuery->toArray')
            ->andReturn(['query' => 'query-value']);
        $request->shouldReceive('getUri->getHost')
            ->andReturn('hostname');
        $request->shouldReceive('getUri->getPort')
            ->andReturn(9001);
        $request->shouldReceive('getUri->getPath')
            ->andReturn('/request/path');
        $request->shouldReceive('getUri->getQuery')
            ->andReturn('query=query-value');

        putenv('ENV_NAME=envValue');
        $subject = Context::fromRequest($request);

        verify($subject->get('request.headers.Request-Header'))->is()->sameAs('header value');
        verify($subject->get('request.headerEntries.0.key'))->is()->sameAs('Request-Header');
        verify($subject->get('request.headerEntries.0.value'))->is()->sameAs('header value');
        verify($subject->get('request.queryEntries.0.key'))->is()->sameAs('query');
        verify($subject->get('request.queryEntries.0.value'))->is()->sameAs('query-value');
        verify($subject->get('request.url.host'))->is()->sameAs('hostname');
        verify($subject->get('request.url.hostname'))->is()->sameAs('hostname:9001');
        verify($subject->get('request.url.port'))->is()->sameAs(9001);
        verify($subject->get('request.url.pathname'))->is()->sameAs('/request/path');
        verify($subject->get('request.url.search'))->is()->sameAs('?query=query-value');
        verify($subject->get('request.url.query.query'))->is()->sameAs('query-value');
        verify($subject->get('env.ENV_NAME'))->is()->sameAs('envValue');
    }

    /**
     * @dataProvider builtinDataProvider
     */
    public function testGetBuiltin($value): void
    {
        $subject = new Context([]);

        verify($subject->get($value))->is()->sameAs($value);
    }

    public function testIsBuiltinValue(): void
    {
        $subject = new Context([]);

        verify($subject->isBuiltinValue('GET'))->is()->true();
        verify($subject->isBuiltinValue('mustache'))->is()->true();
        verify($subject->isBuiltinValue('utf8'))->is()->true();
        verify($subject->isBuiltinValue('101'))->is()->true();
        verify($subject->isBuiltinValue(201))->is()->true();

        verify($subject->isBuiltinValue('some other value'))->is()->false();
        verify($subject->isBuiltinValue('99'))->is()->false();
        verify($subject->isBuiltinValue(700))->is()->false();
        verify($subject->isBuiltinValue(3.14))->is()->false();
        verify($subject->isBuiltinValue('6.28'))->is()->false();
    }

    public function testIsStatusCode(): void
    {
        $subject = new Context([]);

        verify($subject->isStatusCode('101'))->is()->true();
        verify($subject->isStatusCode(201))->is()->true();

        verify($subject->isStatusCode('99'))->is()->false();
        verify($subject->isStatusCode(700))->is()->false();
        verify($subject->isStatusCode(3.14))->is()->false();
        verify($subject->isStatusCode('6.28'))->is()->false();
    }

    /**
     * @dataProvider builtinDataProvider
     */
    public function testSetBuiltin($value): void
    {
        $subject = new Context([]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot override a builtin value.');

        $subject->set($value, 'some value');
    }
}
