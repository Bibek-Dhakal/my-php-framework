<?php
namespace Bibek8366\MyPhpApp\Helpers;
use stdClass;
use InvalidArgumentException;
use Exception;
/**
 * Represents a route with middleware stack to process incoming requests.
 */
class Route {
    public string $path; // The path of the route
    public string $method; // The HTTP method of the route
    protected array $stack = []; // Stack of middleware functions
    protected int $currentIndex; // Current index in the middleware stack
    protected $errorHandler; // Error handler callable

    /**
     * Constructs a Route object with the specified path, HTTP method, and middleware stack.
     *
     * @param string $path         The path of the route.
     * @param string $method       The HTTP method of the route.
     * @param array  $middlewares  An array of middleware functions.
     * @throws InvalidArgumentException If any non-callable item is passed.
     */
    public function __construct(string $path, string $method, array $middlewares) {
        $this->path = $path;
        $this->method = $method;
        $this->currentIndex = 0;
        foreach ($middlewares as $middleware) {
            if (is_callable($middleware)) {
                $this->stack[] = $middleware;
            } else {
                // If any non-callable item is passed, throw an exception in IDE
                throw new InvalidArgumentException('Non-callable item passed.');
            }
        }
    }

    /**
     * Runs the middleware stack with the provided error handler.
     *
     * @param callable $errorHandler  The error handler function.
     * @throws Exception              If error handler is not a function.
     */
    public function run(callable $errorHandler): void {
        if (empty($this->stack)) {
            return;
        }
        if (is_callable($errorHandler)) {
            $this->errorHandler = $errorHandler;
        } else {
            // Error will be handled when this method is called by the app bootstrap method
            throw new Exception('Error handler is not a function');
        }
        if (is_callable($this->stack[$this->currentIndex])) {
            $next = $this->getNextFnDef();
            call_user_func($this->stack[$this->currentIndex], $next);
        } else {
            // Error will be handled when this method is called by the app bootstrap method
            throw new Exception('Middleware must be a callable');
        }
    }

    /**
     * Retrieves the definition of the next middleware function.
     *
     * @return callable  The next middleware function.
     */
    public function getNextFnDef(): callable {
        return function ($error = new stdClass()) {
            if (!empty((array) $error) ?? false) {
                // Error object is not empty
                if (is_callable($this->errorHandler)) {
                    call_user_func($this->errorHandler, $error);
                }
            } else {
                // Error object is empty
                if (isset($this->stack[$this->currentIndex + 1]) && is_callable($this->stack[$this->currentIndex + 1])) {
                    $this->currentIndex++;
                    $next = $this->getNextFnDef();
                    call_user_func($this->stack[$this->currentIndex], $next);
                }
            }
        };
    }

}


