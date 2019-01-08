<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward;

use Zend\Http\PhpEnvironment\Request;
use Zend\Http\Response;

class Controller
{
    public const STANDARD_FIELDS = ['status', 'headers', 'body'];

    /** @var Context */
    private $context;

    /** @var Definition */
    private $definition;

    /** @var DefinitionIterator */
    private $definitionIterator;

    /** @var Request */
    private $request;

    public function __construct(Request $request, string $upwardConfig)
    {
        $this->request    = $request;
        $this->context    = Context::fromRequest($request);
        $this->definition = Definition::fromYamlFile($upwardConfig);

        foreach (self::STANDARD_FIELDS as $key) {
            if (!$this->definition->has($key)) {
                throw new \RuntimeException("Definition YAML is missing required key: ${key}");
            }
        }

        $this->definitionIterator = new DefinitionIterator($this->definition, $this->context);
    }

    /**
     * Executes request and returns response.
     */
    public function __invoke(): Response
    {
        try {
            foreach (self::STANDARD_FIELDS as $key) {
                ${$key} = $this->definitionIterator->get($key);

                if (${$key} instanceof Response) {
                    return ${$key};
                }
            }
        } catch (\RuntimeException $e) {
            $status  = 500;
            $headers = [];
            $body    = $e->getMessage();
        }

        $response = new Response();

        $response->setStatusCode($status);
        $response->getHeaders()->addHeaders($headers);
        $response->setContent($body);

        return $response;
    }
}
