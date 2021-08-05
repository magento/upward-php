<?php

declare(strict_types=1);

namespace Magento\Upward\Resolver;

use Magento\Upward\Definition;
use Magento\UpwardConnector\Model\Computed\ComputedInterface;

class Computed extends AbstractResolver
{
    /** @var \Magento\UpwardConnector\Model\ComputedPool */
    private $computed;

    public function __construct()
    {
        $this->computed = \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\UpwardConnector\Model\ComputedPool::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getIndicator(): string
    {
        return 'computed';
    }

    /**
     * {@inheritdoc}
     */
    public function isValid(Definition $definition): bool
    {
        return $definition->has('type');
    }

    /**
     * {@inheritdoc}
     */
    public function resolve($definition)
    {
        $computeType = $this->getIterator()->get('type', $definition);
        $computeResolver = $this->computed->getItem($computeType);
        if (!($computeResolver instanceof ComputedInterface)) {
            throw new \RuntimeException(sprintf(
                'Compute definition %s is not valid.',
                $computeType
            ));
        }

        return $computeResolver->resolve($this->getIterator()->getContext());
    }
}
