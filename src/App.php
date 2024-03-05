<?php
// App.php
namespace MyPhpApp;
use Exception;
use InvalidArgumentException;
use MyPhpApp\Helpers\Request;
use MyPhpApp\Helpers\Router;
use MyPhpApp\Helpers\ValidationUtils;
use MyPhpApp\Helpers\ErrorUtils;
use MyPhpApp\Helpers\CustomError;
use MyPhpApp\Helpers\DbUtils;
use MyPhpApp\Helpers\FileUtils;
use MyPhpApp\Helpers\MailUtils;
use MyPhpApp\Helpers\PaymentUtils;
/**
 * The main application class responsible for bootstrapping the application, managing routes,
 * handling requests, and serving static files. Also provides access to utility classes.
 */
class App {
    /** @var Router[] An associative array of route paths and their corresponding routers. */
    protected static array $stack = [];

    /**
     * Adds a new route to the application.
     *
     * @param string   $path        The route path.
     * @param string   $method      The HTTP method for the route (e.g., GET, POST).
     * @param callable[] $middlewares An array of middleware functions to be executed for the route.
     * @throws InvalidArgumentException If any non-callable item is passed as middleware.
     */
    public static function addRoute(string $path, string $method, array $middlewares): void {
        $callableMiddlewares = [];
        foreach ($middlewares as $middleware) {
            if (!is_callable($middleware)) {
                // If any non-callable item is passed, throw an exception in IDE -------
                throw new InvalidArgumentException('Non-callable item passed.');
            }
            $callableMiddlewares[] = $middleware;
        }
        $router = new Router();
        $router->addRoute($path, $method, $callableMiddlewares);
        self::addRouter($path, $router);
    }

    /**
     * Retrieves the router associated with the application.
     *
     * @return Router The router object.
     */
    public static function router(): Router {
        return new Router();
    }

    /**
     * Adds a router to the application stack.
     *
     * @param string $path   The route path associated with the router.
     * @param Router $router The router object.
     */
    public static function addRouter(string $path, Router $router): void {
        self::$stack[$path] = $router;
    }

    /**
     * Bootstrap the application by handling incoming requests, executing middleware,
     * and serving static files if no routes match.
     *
     * @param callable $errorHandler       The error handler function to be called in case of exceptions.
     * @param string   $fullPathToStaticFolder The full path to the static folder containing static files.
     */
    public static function bootstrap(callable $errorHandler, string $fullPathToStaticFolder): void {
        try {
            // Skip bootstrapping if no routes are defined
            if (empty(self::$stack)) {
                return;
            }
            $request = new Request();
            foreach (self::$stack as $path => $router) {
                // Remove query parameter placeholders from route path if exists
                // So that they can be placed in the route path without disturbing the route path matching
                // Incoming request path is already stripped of query parameters, so we don't need to do that here
                $path = preg_replace('/\/:\w+&\w+/', '', $path);
                if (str_starts_with($request->path, $path)) {
                    $routes = $router->getStack();
                    if (empty($routes)) {
                        // No routes in matched router, return
                        return;
                    }
                    foreach ($routes as $route) {
                        if ($route->path === $request->path && $route->method === $request->method) {
                            $route->run($errorHandler);
                        } else {
                            self::serveStaticFile($fullPathToStaticFolder, $request->path);
                        }
                        break; /* Break out of the loop if a route is matched or a file is served (IMPORTANT!) */
                    }
                } else {
                    self::serveStaticFile($fullPathToStaticFolder, $request->path);
                }
                break; /* Break out of the loop if a router is matched or a file is served (IMPORTANT!) */
            }
        } catch (Exception $e) {
            $errorHandler($e);
        }
    }

    /**
     * Serves a static file from the specified static folder.
     *
     * @param string $fullPathToStaticFolder The full path to the static folder containing static files.
     * @param string $requestPath  The requested path for the static file.
     * @throws CustomError  If the static file is not found or not readable.
     */
    private static function serveStaticFile(
        string $fullPathToStaticFolder,
        string $requestPath): void {
        // passed path is already stripped of query parameters (passed from bootstrap method)
        // Check if the request path has an extension
        if (!pathinfo($requestPath, PATHINFO_EXTENSION)) {
            // If no extension, file is not found
            $errorUtils = new ErrorUtils();
            // Throw a custom exception which ll be caught by the error handler when the app is bootstrapped
            throw $errorUtils->customError(
                "FileNotFoundException",
                "App",
                "serveStaticFile",
                "Not Found",
                404,
                debug_backtrace()
            );
        }
        // Join the static folder path and the request path
        $fullFilePath = "$fullPathToStaticFolder$requestPath";
        // Check if the file exists and is readable
        if (file_exists($fullFilePath) && is_readable($fullFilePath)) {
            // Set appropriate headers
            header("Content-Type: " . mime_content_type($fullFilePath));
            header('Content-Length: ' . filesize($fullFilePath));
            // Output the file contents
            readfile($fullFilePath);
        } else {
            // file is not found or not readable
            $errorUtils = new ErrorUtils();
            // Throw a custom exception which ll be caught by the error handler when the app is bootstrapped
            throw $errorUtils->customError(
                "FileNotFoundException",
                "App",
                "serveStaticFile",
                "Not Found",
                404,
                debug_backtrace()
            );
        }
    }

    /**
     * Retrieves an instance of ErrorUtils.
     *
     * @return ErrorUtils The ErrorUtils instance.
     */
    public static function errorUtils(): ErrorUtils {
        return new ErrorUtils();
    }

    /**
     * Retrieves an instance of FileUtils.
     *
     * @return FileUtils The FileUtils instance.
     */
    public static function fileUtils(): FileUtils {
        return new FileUtils();
    }

    /**
     * Retrieves an instance of DbUtils.
     *
     * @return DbUtils The DbUtils instance.
     */
    public static function dbUtils(): DbUtils {
        return new DbUtils();
    }

    /**
     * Retrieves an instance of ValidationUtils.
     *
     * @return ValidationUtils The ValidationUtils instance.
     */
    public static function validationUtils(): ValidationUtils {
        return new ValidationUtils();
    }

    /**
     * Retrieves an instance of MailUtils.
     *
     * @return MailUtils The MailUtils instance.
     */
    public static function mailUtils(): MailUtils {
        return new MailUtils();
    }

    /**
     * Retrieves an instance of PaymentUtils.
     *
     * @return PaymentUtils The PaymentUtils instance.
     */
    public static function paymentUtils(): PaymentUtils {
        return new PaymentUtils();
    }
}


