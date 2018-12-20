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

    private $lookup = '';

    /**
     * Convert Yaml file to a Definition.
     *
     * @return static
     */
    public static function fromYamlFile(string $filePath): self
    {
        $instance = new static(Yaml::parseFile($filePath));
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
            $value->lookup = (empty($this->lookup) ? '' : $this->lookup . '.') . $lookup;
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

    public function getLookupPath(): string
    {
        return $this->lookup;
    }

    /**
     * Set path to directory containing definition YAML.
     */
    public function setBasepath(string $path): void
    {
        $this->basepath = realpath($path);
    }
}
