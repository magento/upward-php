<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

use Laminas\Http\PhpEnvironment\Request;
use Magento\Upward\Controller;

try {
    require_once __DIR__ . '/../vendor/autoload.php';
} catch (\Exception $e) {
    echo 'Autoload error: ' . $e->getMessage();
    exit(1);
}

$upwardConfig = getenv('UPWARD_PHP_UPWARD_PATH');
if (!$upwardConfig) {
    echo 'No path to UPWARD YAML file provided.' . \PHP_EOL;
    exit(1);
}

return new Controller(new Request(), $upwardConfig);
