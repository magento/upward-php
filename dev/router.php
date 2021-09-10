<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

$controller = require __DIR__ . '/bootstrap-controller.php';
/** @var \Laminas\Http\Response $response */
$response = $controller();

header($response->renderStatusLine());
foreach ($response->getHeaders() as $header) {
    header($header->toString());
}

if ($response instanceof \Laminas\Http\Response\Stream) {
    echo $response->getBody();
} else {
    echo $response->getContent();
}
