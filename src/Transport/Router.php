<?php

/**
 * This file is part of Temporal package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Temporal\Client\Transport;

use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use Temporal\Client\Transport\Protocol\Command\RequestInterface;
use Temporal\Client\Transport\Router\RouteInterface;

use function React\Promise\reject;

final class Router implements RouterInterface
{
    /**
     * @var string
     */
    private const ERROR_ROUTE_UNIQUENESS = 'Route "%s" has already been registered';

    /**
     * @var string
     */
    private const ERROR_ROUTE_NOT_FOUND = 'Method "%s" is not registered';

    /**
     * @var array|RouteInterface[]
     */
    private array $routes = [];

    /**
     * {@inheritDoc}
     */
    public function add(RouteInterface $route, bool $overwrite = false): void
    {
        if ($overwrite === false && isset($this->routes[$route->getName()])) {
            throw new \InvalidArgumentException(\sprintf(self::ERROR_ROUTE_UNIQUENESS, $route->getName()));
        }

        $this->routes[$route->getName()] = $route;
    }

    /**
     * {@inheritDoc}
     */
    public function remove(RouteInterface $route): void
    {
        unset($this->routes[$route->getName()]);
    }

    /**
     * @param RequestInterface $request
     * @return RouteInterface|null
     */
    public function match(RequestInterface $request): ?RouteInterface
    {
        return $this->routes[$request->getName()] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function dispatch(RequestInterface $request, array $headers = []): PromiseInterface
    {
        $route = $this->match($request);

        if ($route === null) {
            $error = \sprintf(self::ERROR_ROUTE_NOT_FOUND, $request->getName());

            return reject(new \BadMethodCallException($error));
        }

        $deferred = new Deferred();

        try {
            $route->handle($request->getParams(), $headers, $deferred);
        } catch (\Throwable $e) {
            $deferred->reject($e);
        }

        return $deferred->promise();
    }
}
