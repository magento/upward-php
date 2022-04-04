<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward;

class ResolverFactory
{
    public const RESOLVER_TYPE_CONDITIONAL = 'conditional';
    public const RESOLVER_TYPE_DIRECTORY   = 'directory';
    public const RESOLVER_TYPE_FILE        = 'file';
    public const RESOLVER_TYPE_INLINE      = 'inline';
    public const RESOLVER_TYPE_PROXY       = 'proxy';
    public const RESOLVER_TYPE_SERVICE     = 'service';
    public const RESOLVER_TYPE_TEMPLATE    = 'template';
    public const RESOLVER_TYPE_URL         = 'url';

    /**
     * @var array map of resolver key to their class implementation
     */
    private static $resolverClasses = [
        self::RESOLVER_TYPE_CONDITIONAL => Resolver\Conditional::class,
        self::RESOLVER_TYPE_DIRECTORY   => Resolver\Directory::class,
        self::RESOLVER_TYPE_FILE        => Resolver\File::class,
        self::RESOLVER_TYPE_INLINE      => Resolver\Inline::class,
        self::RESOLVER_TYPE_PROXY       => Resolver\Proxy::class,
        self::RESOLVER_TYPE_SERVICE     => Resolver\Service::class,
        self::RESOLVER_TYPE_TEMPLATE    => Resolver\Template::class,
        self::RESOLVER_TYPE_URL         => Resolver\Url::class,
    ];

    /**
     * @var Resolver\ResolverInterface[]
     */
    private static $resolvers = [];

    /**
     * Add a pre-built Resolver to the resolver cache.
     */
    public static function addResolver(string $key, Resolver\ResolverInterface $resolver): void
    {
        self::$resolvers[$key] = $resolver;
    }

    /**
     * Add mapping for a resolver type to class name.
     */
    public static function addResolverClass(string $key, string $className): void
    {
        self::$resolverClasses[$key] = $className;
    }

    /**
     * Get a Resolver for a value, whether scalar or an instance of Definition.
     *
     * @param string|Definition $definition
     */
    public static function get($definition): ?Resolver\ResolverInterface
    {
        return is_scalar($definition) ? self::getForScalar($definition) : self::getForDefinition($definition);
    }

    /**
     * Return a resolver from instance cache by its type,
     * otherwise instantiate one based on its type and cache it.
     *
     * @throws \RuntimeException if there is no cached instance or configured class for $resolverType
     */
    private static function build(string $resolverType): Resolver\ResolverInterface
    {
        if (!\array_key_exists($resolverType, self::$resolvers)) {
            if (!\array_key_exists($resolverType, self::$resolverClasses)) {
                throw new \RuntimeException('No resolver class defined for resolver ' . $resolverType);
            }

            self::addResolver($resolverType, new self::$resolverClasses[$resolverType]());
        }

        return self::$resolvers[$resolverType];
    }

    /**
     * Get a Resolver for a Definition.
     *
     * @throws \RuntimeException if $definition is not valid for the Resolver
     */
    private static function getForDefinition(Definition $definition): Resolver\ResolverInterface
    {
        return $definition->has('resolver')
            ? self::build($definition->get('resolver'))
            : self::inferResolver($definition);
    }

    /**
     * Get a resolver for a scalar type value.
     */
    private static function getForScalar(string $lookup): ?Resolver\ResolverInterface
    {
        foreach (array_keys(self::$resolverClasses) as $type) {
            $resolver = self::build($type);

            if ($resolver->isShorthand($lookup)) {
                return $resolver;
            }
        }

        return null;
    }

    /**
     * Return a resolver for a Definition based on if that Definition has the resolver's indicator.
     *
     * @throws \RuntimeException if no resolver can be inferred
     */
    private static function inferResolver(Definition $definition): Resolver\ResolverInterface
    {
        foreach (array_keys(self::$resolverClasses) as $type) {
            $resolver = self::build($type);

            if ($definition->has($resolver->getIndicator())) {
                return $resolver;
            }

            if ($resolver instanceof Resolver\AbstractResolver) {
                foreach ($resolver->getDeprecatedIndicators() as $deprecatedIndicator) {
                    if ($definition->has($deprecatedIndicator)) {
                        return $resolver;
                    }
                }
            }
        }

        throw new \RuntimeException('No resolver found for definition: ' . json_encode($definition));
    }
}
