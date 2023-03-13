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

// populate the $_SERVER superglobal with environment variables if we're running with PHP's built-in server
if (PHP_SAPI === 'cli-server') {
    $_SERVER = array_merge(getenv(), $_SERVER);
}
$upwardConfig = $_SERVER['UPWARD_PHP_UPWARD_PATH'] ?? null;
if (!$upwardConfig) {
    echo 'No path to UPWARD YAML file provided.' . \PHP_EOL;
    exit(1);
}

return new Controller(new Request(), $upwardConfig);
