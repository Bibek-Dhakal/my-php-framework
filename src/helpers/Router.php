<?php
namespace Bibek8366\MyPhpApp\Helpers;
use InvalidArgumentException;
/**
 * Handles routing by managing a stack of routes with middleware.
 */
class Router {
    protected array $stack = []; // Stack of Route objects

    /**
     * Adds a new route to the router with the specified path, HTTP method, and middleware.
     *
     * @param string   $path         The path of the route.
     * @param string   $method       The HTTP method of the route.
     * @param callable[] $middlewares An array of middleware functions.
     * @throws InvalidArgumentException If any non-callable item is passed.
     */
    public function addRoute(string $path, string $method, bool $is_ajax, array $middlewares): void {
        $callableMiddlewares = [];
        foreach ($middlewares as $middleware) {
            if (!is_callable($middleware)) {
                // If any non-callable item is passed, throw an exception
                throw new InvalidArgumentException('Non-callable item passed.');
            }
            $callableMiddlewares[] = $middleware;
        }
        $route = new Route($path, $method, $is_ajax, $callableMiddlewares);
        $this->stack[] = $route;
    }

    /**
     * Retrieves the stack of routes.
     *
     * @return array The array containing Route objects.
     */
    public function getStack(): array {
        return $this->stack;
    }
}


