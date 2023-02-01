<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward;

use Symfony\Component\Yaml\Yaml;

class Definition extends AbstractKeyValueStore
{
    /**
     * @var string
     */
    private $basepath;

    /**
     * @var string
     */
    private $treeAddress = '';

    /**
     * Set basepath to cwd on init.
     *
     * {@inheritdoc}
     */
    public function __construct(array $data)
    {
        $this->setBasepath(getcwd());

        parent::__construct($data);
    }

    /**
     * Convert Yaml file to a Definition.
     *
     * @return static
     */
    public static function fromYamlFile(string $filePath): self
    {
        $data = Yaml::parseFile($filePath);

        if (!\is_array($data)) {
            throw new \InvalidArgumentException("File {$filePath} could not be parsed as YAML.");
        }

        $instance = new static($data);
        $instance->setBasepath(\dirname($filePath));

        return $instance;
    }

    /**
     * Make sure to pass basepath into child definitions.
     *
     * {@inheritdoc}
     */
    public function get($lookup)
    {
        $value = parent::get($lookup);

        if ($value instanceof self) {
            $value->setBasepath($this->getBasepath());
            $value->treeAddress = (empty($this->treeAddress) ? '' : $this->treeAddress . '.') . $lookup;
        }

        return $value;
    }

    /**
     * Get the directory name where definition YAML is stored.
     */
    public function getBasepath(): string
    {
        return $this->basepath;
    }

    /**
     * Get a dot separated address of where this node belongs in the definition tree.
     */
    public function getTreeAddress(): string
    {
        return $this->treeAddress;
    }

    /**
     * Set path to directory containing definition YAML.
     */
    public function setBasepath(string $path): void
    {
        $this->basepath = realpath($path);
    }
}
