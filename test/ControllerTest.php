<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward\Test;

use Laminas\Http\PhpEnvironment\Request;
use Laminas\Http\Response;
use Magento\Upward\Controller;
use Magento\Upward\DefinitionIterator;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use function BeBat\Verify\verify;

/**
 * @runTestsInSeparateProcesses
 */
class ControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var DefinitionIterator|Mockery\MockInterface
     */
    private $mockIterator;

    /**
     * @var Request|Mockery\MockInterface
     */
    private $mockRequest;

    protected function setUp(): void
    {
        $this->mockRequest  = Mockery::mock(Request::class)->makePartial();
        $this->mockIterator = Mockery::mock('overload:' . DefinitionIterator::class);

        $this->mockIterator->shouldReceive('get')
            ->once()->with('status')->andReturn(200)->byDefault();
        $this->mockIterator->shouldReceive('get')
            ->once()->with('headers')->andReturn(['content-type' => 'text/plain'])->byDefault();
        $this->mockIterator->shouldReceive('get')
            ->once()->with('body')->andReturn('Response Body')->byDefault();
    }

    public function testInvokeValid(): void
    {
        $controller = new Controller($this->mockRequest, 'pwa/upward-config-inline.yml');
        $response   = $controller();

        verify($response->getStatusCode())->is()->sameAs(200);
        verify($response->getHeaders())->isNot()->empty();
        verify($response->getContent())->is()->sameAs('Response Body');
    }

    public function testInvokeWithException(): void
    {
        $this->mockIterator->shouldReceive('get')
            ->once()->with('status')->andThrow(new \RuntimeException('Exception Message'));
        $this->mockIterator->shouldReceive('get')->with('status')->never();
        $this->mockIterator->shouldReceive('get')->with('headers')->never();

        $controller = new Controller($this->mockRequest, 'pwa/upward-config-inline.yml');
        $response   = $controller();

        verify($response->getStatusCode())->is()->sameAs(500);
        verify($response->getHeaders())->is()->empty();
        verify($response->getContent())->is()->sameAs('{"error":"Exception Message"}');
    }

    public function testInvokeWithResponse(): void
    {
        $response = new Response();

        $this->mockIterator->shouldReceive('get')
            ->once()->with('status')->andReturn($response);
        $this->mockIterator->shouldReceive('get')->with('status')->never();
        $this->mockIterator->shouldReceive('get')->with('headers')->never();

        $controller = new Controller($this->mockRequest, 'pwa/upward-config-inline.yml');
        $result     = $controller();

        verify($result)->is()->sameAs($response);
    }
}
