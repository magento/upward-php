<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward;

use Laminas\Http\PhpEnvironment\Request;
use Laminas\Http\Response;

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

    /**
     * @param ?array $additionalResolvers
     *
     * @throws \RuntimeException if a required key is missing from $upwardCofig file
     */
    public function __construct(
        Request $request,
        string $upwardConfig,
        ?array $additionalResolvers = []
    ) {
        $this->request    = $request;
        $this->context    = Context::fromRequest($request);
        $this->definition = Definition::fromYamlFile($upwardConfig);

        foreach (self::STANDARD_FIELDS as $key) {
            if (!$this->definition->has($key)) {
                throw new \RuntimeException("Definition YAML is missing required key: {$key}");
            }
        }

        $this->definitionIterator = new DefinitionIterator(
            $this->definition,
            $this->context,
            $additionalResolvers
        );
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
            $body    = json_encode(['error' => $e->getMessage()]);
        }

        $response = new Response();

        $response->setStatusCode($status);
        $response->getHeaders()->addHeaders($headers);
        $response->setContent($body);

        return $response;
    }
}
