<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward\Test\Resolver;

use Magento\Upward\Definition;
use Magento\Upward\Resolver\Inline;
use PHPUnit\Framework\TestCase;
use function BeBat\Verify\verify;

class InlineTest extends TestCase
{
    /**
     * @var Inline
     */
    private $resolver;

    protected function setUp(): void
    {
        $this->resolver = new Inline();
    }

    public function testIndicator(): void
    {
        verify($this->resolver->getIndicator())->is()->sameAs('inline');
    }

    public function testIsShorthand(): void
    {
        verify($this->resolver->isShortHand('anything'))->is()->false();
    }

    public function testIsValid(): void
    {
        $validDefinition   = new Definition(['inline' => 'some inline value']);
        $invalidDefinition = new Definition(['foo' => 'bar', 'child' => ['inline' => 'child inline value']]);

        verify($this->resolver->isValid($validDefinition))->is()->true();
        verify($this->resolver->isValid($invalidDefinition))->is()->false();
    }

    public function testResolve(): void
    {
        $definition = new Definition(['inline' => 'some inline value']);

        verify($this->resolver->resolve($definition))->is()->sameAs('some inline value');
    }
}
