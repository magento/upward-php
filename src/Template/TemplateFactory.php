<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward\Template;

class TemplateFactory
{
    public const TEMPLATE_MUSTACHE = 'mustache';

    /**
     * @var array map of template to renderer implementations
     */
    private static $templateClasses = [
        self::TEMPLATE_MUSTACHE => Mustache::class,
    ];

    /**
     * @throws \InvalidArgumentException if class isn't found or is not the correct type
     */
    public static function get(string $basePath, ?string $engine): TemplateInterface
    {
        // default to mustache if null passed
        $engine = $engine ?? self::TEMPLATE_MUSTACHE;
        if (array_key_exists($engine, self::$templateClasses)) {
            $engine = new self::$templateClasses[$engine]($basePath);
            if ($engine instanceof TemplateInterface) {
                return $engine;
            }
        }

        throw new \InvalidArgumentException("${engine} could not be found or does not implement TemplateInterface");
    }
}
