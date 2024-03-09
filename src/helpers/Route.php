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
    public bool $is_ajax; // is_ajax flag
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
    public function __construct(string $path, string $method, bool $is_ajax, array $middlewares) {
        $this->path = $path;
        $this->method = $method;
        $this->is_ajax = $is_ajax;
        $this->currentIndex = 0;
        foreach ($middlewares as $middleware) {
            if (is_callable($middleware)) {
                $this->stack[] = $middleware;
            } else {
                error_log('Non-callable item passed.');
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
     try {
        if (empty($this->stack)) {
            return;
        }
        $this->errorHandler = $errorHandler;
        if (is_callable($this->stack[$this->currentIndex])) {
            $next = $this->getNextFnDef();
            call_user_func($this->stack[$this->currentIndex], $next);
        } else {
            error_log('Middleware must be a callable');
            // Error will be handled when this method is called by the app bootstrap method
            throw new Exception('Middleware must be a callable');
        }
     } catch (Exception $e) {
        $errorHandler($e, $this->is_ajax);
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
                    call_user_func($this->errorHandler, $error, $this->is_ajax);
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


