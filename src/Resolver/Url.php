<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward\Resolver;

use Laminas\Uri\UriFactory;
use Magento\Upward\Definition;

class Url extends AbstractResolver
{
    public const FAKE_BASE_HOST     = 'upward-fake.localhost';
    public const FAKE_BASE_URL      = 'https://' . self::FAKE_BASE_HOST;
    public const NON_RELATIVE_PARTS = ['protocol', 'port', 'username', 'password'];

    /**
     * {@inheritdoc}
     */
    public function getIndicator(): string
    {
        return 'baseUrl';
    }

    /**
     * {@inheritdoc}
     */
    public function isValid(Definition $definition): bool
    {
        if ($definition->has('query')) {
            $query = $this->getIterator()->get('query', $definition);
            if (!\is_array($query)) {
                return false;
            }
        }

        if ($definition->has('password') && !$definition->has('username')) {
            return false;
        }

        if ($this->getIterator()->get('baseUrl', $definition) === false && !$definition->has('hostname')) {
            foreach (self::NON_RELATIVE_PARTS as $nonRelativePart) {
                if ($definition->has($nonRelativePart)) {
                    return false;
                }
            }
        }

        return parent::isValid($definition);
    }

    /**
     * {@inheritdoc}
     */
    public function resolve($definition)
    {
        if (!$definition instanceof Definition) {
            throw new \InvalidArgumentException('$definition must be an instance of ' . Definition::class);
        }

        $baseUrl = $this->getIterator()->get('baseUrl', $definition)
            ?: self::FAKE_BASE_URL;
        $uri = UriFactory::factory($baseUrl);

        if ($definition->has('hostname')) {
            $uri->setHost($this->getIterator()->get('hostname', $definition));
        }

        if ($definition->has('protocol')) {
            $uri->setScheme(str_replace(':', '', $this->getIterator()->get('protocol', $definition)));
        }

        if ($definition->has('pathname')) {
            $pathname        = $this->getIterator()->get('pathname', $definition);
            $currentPathname = $uri->getPath();
            if ($pathname[0] !== '/') {
                if ($currentPathname === null) {
                    $pathname = "/{$pathname}";
                } elseif ($currentPathname[\strlen($currentPathname) - 1] === '/') {
                    $pathname = $currentPathname . $pathname;
                } else {
                    $trimmedCurrentPathname = substr($currentPathname, 0, strrpos($currentPathname, '/') + 1);
                    $pathname               = $trimmedCurrentPathname . $pathname;
                }
            }

            $uri->setPath($pathname);
        }

        if ($definition->has('search')) {
            parse_str($this->getIterator()->get('search', $definition), $searchArray);
            $uri->setQuery(array_merge($uri->getQueryAsArray(), $searchArray));
        }

        if ($definition->has('query')) {
            $mergedQuery = array_merge($uri->getQueryAsArray(), $this->getIterator()->get('query', $definition));
            $uri->setQuery($mergedQuery);
        }

        if ($definition->has('port')) {
            $uri->setPort($this->getIterator()->get('port', $definition));
        }

        if ($definition->has('username')) {
            $userInfo = $this->getIterator()->get('username', $definition);
            if ($definition->has('password')) {
                $userInfo .= ':' . $this->getIterator()->get('password', $definition);
            }
            $uri->setUserInfo($userInfo);
        }

        if ($definition->has('hash')) {
            $uri->setFragment(str_replace('#', '', $this->getIterator()->get('hash', $definition)));
        }

        $returnUrl = $uri->toString();

        return $uri->getHost() === self::FAKE_BASE_HOST
            ? str_replace(self::FAKE_BASE_URL, '', $returnUrl)
            : $returnUrl;
    }
}
