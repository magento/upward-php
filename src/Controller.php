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
        \Zend\Http\PhpEnvironment\Request $request,
        string $upwardConfig
    ) {
        $this->request = $request;
        $this->context = Context::fromRequest($request);
        $this->definition = Definition::fromYamlFile($upwardConfig);
        $this->definitionTraverser = new DefinitionTraverser($this->definition, $this->context);
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
            $response->setStatusCode($this->definitionTraverser->get('status'));
            $response->getHeaders()->addHeaders($this->definitionTraverser->get('headers'));
            $response->setContent($this->definitionTraverser->get('body'));
        } catch (\RuntimeException $e) {
            $response->setStatusCode(500);
            $response->getHeaders()->clearHeaders();
            $response->setContent($e->getMessage());
        }

        return $response;
    }
}
