<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward\Test;

use Magento\Upward\Controller;
use Magento\Upward\DefinitionIterator;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Zend\Http\PhpEnvironment\Request;
use function BeBat\Verify\verify;

/**
 * @runTestsInSeparateProcesses
 */
class ControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testInvokeValid(): void
    {
        $request                = Mockery::mock(Request::class)->makePartial();
        $definitionIteratorMock = Mockery::mock('overload:' . DefinitionIterator::class);
        $definitionIteratorMock->shouldReceive('get')->once()->with('status')->andReturn(200);
        $definitionIteratorMock->shouldReceive('get')->once()->with('headers')->andReturn([
            'content-type' => 'text/plain',
        ]);
        $definitionIteratorMock->shouldReceive('get')->once()->with('body')->andReturn('Response Body');
        $controller = new Controller($request, 'pwa/upward-config-sample.yml');
        $response   = $controller();
        verify($response->getStatusCode())->is()->sameAs(200);
        verify($response->getHeaders())->isNot()->empty();
        verify($response->getContent())->is()->sameAs('Response Body');
    }

    public function testInvokeWithException(): void
    {
        $request                = Mockery::mock(Request::class)->makePartial();
        $definitionIteratorMock = Mockery::mock('overload:' . DefinitionIterator::class);
        $definitionIteratorMock->shouldReceive('get')->once()->with('status')->andReturn(200);
        $definitionIteratorMock->shouldReceive('get')->once()->with('headers')->andReturn([
            'content-type' => 'text/plain',
        ]);
        $definitionIteratorMock->shouldReceive('get')->once()->with('body')->andThrow(
            new \RuntimeException('Exception Message')
        );
        $controller = new Controller($request, 'pwa/upward-config-sample.yml');
        $response   = $controller();
        verify($response->getStatusCode())->is()->sameAs(500);
        verify($response->getHeaders())->is()->empty();
        verify($response->getContent())->is()->sameAs('Exception Message');
    }
}
