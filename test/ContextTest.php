<?php

declare(strict_types=1);

namespace Magento\Upward\Test;

use Magento\Upward\Context;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Zend\Http\PhpEnvironment\Request;
use function BeBat\Verify\verify;

class ContextTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function builtinDataProvider(): array
    {
        return [
            [true],
            [false],
            ['GET'],
            ['POST'],
            ['mustache'],
            ['text/plain'],
            ['utf8'],
            [100],
            ['100'],
            [599],
            ['599'],
        ];
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
        $request->shouldReceive('getEnv->toArray')
            ->andReturn(['ENV_NAME' => 'env value']);

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
        verify($subject->get('env.ENV_NAME'))->is()->sameAs('env value');
    }

    /**
     * @dataProvider builtinDataProvider
     */
    public function testGetBuiltin($value): void
    {
        $subject = new Context([]);

        verify($subject->get($value))->is()->sameAs($value);
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
