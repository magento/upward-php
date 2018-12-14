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
     * Convert Yaml file to a Definition.
     *
     * @param string $filePath
     *
     * @return static
     */
    public static function fromYamlFile(string $filePath): self
    {
        return new static(Yaml::parseFile($filePath));
    }
}
