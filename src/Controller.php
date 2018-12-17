<?php
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

    /** @var DefinitionTraverser */
    private $definitionTraverser;

    public function __construct(
        \Zend\Http\PhpEnvironment\Request $request
    ) {
        $this->request = $request;
        $this->context = Context::fromRequest($request);
        // this value will need to be sourced from config?
        $this->definition = Definition::fromYamlFile("pwa/upward-config-sample.yml");
        $this->definitionTraverser = new DefinitionTraverser($this->definition, $this->context);
    }

    public function run()
    {
        $response = new \Zend\Http\Response();
        $response->setStatusCode($this->definitionTraverser->get('status'));
        $response->getHeaders()->addHeaders($this->definitionTraverser->get('headers'));
        $response->setContent($this->definitionTraverser->get('body'));

        return $response;
    }
}
