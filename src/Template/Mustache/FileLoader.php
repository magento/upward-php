<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward\Template\Mustache;

class FileLoader extends \Mustache_Loader_FilesystemLoader
{
    protected function loadFile($name)
    {
        return trim(parent::loadFile($name));
    }
}
