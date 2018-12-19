<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Upward;

class Controller
{
    /** @var \Zend\Http\PhpEnvironment\Request */
    private $request;

    /** @var Context */
    private $context;

    /** @var Definition */
    private $definition;

    /** @var DefinitionIterator */
    private $definitionIterator;

    public function __construct(
        \Zend\Http\PhpEnvironment\Request $request,
        string $upwardConfig
    ) {
        $this->request = $request;
        $this->context = Context::fromRequest($request);
        $this->definition = Definition::fromYamlFile($upwardConfig);
        $this->definitionIterator = new DefinitionIterator($this->definition, $this->context);
    }

    /**
     * Executes request and returns response
     *
     * @return \Zend\Http\Response
     */
    public function __invoke(): \Zend\Http\Response
    {
        $response = new \Zend\Http\Response();
        try {
            $response->setStatusCode($this->definitionIterator->get('status'));
            $response->getHeaders()->addHeaders($this->definitionIterator->get('headers'));
            $response->setContent($this->definitionIterator->get('body'));
        } catch (\RuntimeException $e) {
            $response->setStatusCode(500);
            $response->getHeaders()->clearHeaders();
            $response->setContent($e->getMessage());
        }

        return $response;
    }
}