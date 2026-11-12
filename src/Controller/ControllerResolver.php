<?php

/**
 * Part of the Joomla Framework Application Package
 *
 * @copyright  (C) 2018 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Application\Controller;

use Joomla\Controller\ControllerInterface;
use Joomla\Router\ResolvedRoute;

/**
 * Resolves a controller for the given route.
 *
 * @since  2.0.0
 */
class ControllerResolver implements ControllerResolverInterface
{
    /**
     * Resolve the controller for a route
     *
     * @param  ResolvedRoute  $route  The route to resolve the controller for
     *
     * @return  callable
     *
     * @throws  \InvalidArgumentException
     * @since   2.0.0
     */
    public function resolve(ResolvedRoute $route): callable
    {
        $controller = $route->getController();

        // Try to resolve a callable defined as an array
        if (\is_array($controller)) {
            if (isset($controller[0]) && \is_string($controller[0]) && isset($controller[1])) {
                if (!\class_exists($controller[0])) {
                    throw new \InvalidArgumentException(
                        \sprintf('Cannot resolve controller for URI `%s`', $route->getUri())
                    );
                }

                try {
                    $controller[0] = $this->instantiateController($controller[0]);
                } catch (\ArgumentCountError $error) {
                    throw new \InvalidArgumentException(
                        \sprintf(
                            'Controller `%s` has required constructor arguments, cannot instantiate the class',
                            $controller[0]
                        ),
                        0,
                        $error
                    );
                }
            }

            if (!\is_callable($controller)) {
                throw new \InvalidArgumentException(
                    \sprintf('Cannot resolve controller for URI `%s`', $route->getUri())
                );
            }

            return $controller;
        }

        // Try to resolve an invocable object
        if (\is_object($controller)) {
            if (!\is_callable($controller)) {
                throw new \InvalidArgumentException(
                    \sprintf('Cannot resolve controller for URI `%s`', $route->getUri())
                );
            }

            return $controller;
        }

        if (!\is_string($controller)) {
            throw new \InvalidArgumentException(\sprintf('Cannot resolve controller for URI `%s`', $route->getUri()));
        }

        // Try to resolve a class name if it implements our ControllerInterface, before a function with the same name
        if (\interface_exists(ControllerInterface::class) && \class_exists($controller)) {
            if (!\is_subclass_of($controller, ControllerInterface::class)) {
                trigger_deprecation(
                    'joomla/application',
                    '__DEPLOY_VERSION__',
                    'Resolving the controller class "%s", which does not implement "%s", is deprecated and will throw an exception in 5.0.',
                    $controller,
                    ControllerInterface::class
                );
            }

            try {
                $callable = [$this->instantiateController($controller), 'execute'];
            } catch (\ArgumentCountError $error) {
                throw new \InvalidArgumentException(
                    \sprintf(
                        'Controller `%s` has required constructor arguments, cannot instantiate the class',
                        $controller
                    ),
                    0,
                    $error
                );
            }

            if (!\is_callable($callable)) {
                throw new \InvalidArgumentException(
                    \sprintf('Cannot resolve controller for URI `%s`', $route->getUri())
                );
            }

            return $callable;
        }

        // Try to resolve a known function
        if (\function_exists($controller)) {
            return $controller;
        }

        // Unsupported resolution
        throw new \InvalidArgumentException(\sprintf('Cannot resolve controller for URI `%s`', $route->getUri()));
    }

    /**
     * Instantiate a controller class
     *
     * @param  string  $class  The class to instantiate
     *
     * @return  object  Controller class instance
     *
     * @since   2.0.0
     */
    protected function instantiateController(string $class): object
    {
        return new $class();
    }
}
