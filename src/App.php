<?php
// App.php
namespace Bibek8366\MyPhpApp;
use Exception;
use InvalidArgumentException;
use Bibek8366\MyPhpApp\Helpers\Request;
use Bibek8366\MyPhpApp\Helpers\Router;
use Bibek8366\MyPhpApp\Helpers\Route;
use Bibek8366\MyPhpApp\Helpers\ValidationUtils;
use Bibek8366\MyPhpApp\Helpers\ErrorUtils;
use Bibek8366\MyPhpApp\Helpers\CustomError;
use Bibek8366\MyPhpApp\Helpers\DbUtils;
use Bibek8366\MyPhpApp\Helpers\FileUtils;
use Bibek8366\MyPhpApp\Helpers\MailUtils;
use Bibek8366\MyPhpApp\Helpers\PaymentUtils;
/**
 * The main application class responsible for bootstrapping the application, managing routes,
 * handling requests, and serving static files. Also provides access to utility classes.
 *
 * Note:-
 * Beter give full paths to static folder and views folder and other paths
 * __DIR__.'/path' or $_SERVER['DOCUMENT_ROOT'].'/path'
 * / before path and no slash after path
 *
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
    public static function addRoute(
         $path,
         string $method,
         bool $is_ajax,
         array $middlewares
         ): void {
        $callableMiddlewares = [];
        foreach ($middlewares as $middleware) {
            if (!is_callable($middleware)) {
                // If any non-callable item is passed, throw an exception in IDE -------
                throw new InvalidArgumentException('Non-callable item passed.');
            }
            $callableMiddlewares[] = $middleware;
        }
        $router = new Router();
        $router->addRoute($path, $method, $is_ajax, $callableMiddlewares);
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
                exit;
            }
            $request = new Request();
            $incomingPath = $request->path;
            $incomingMethod = $request->method;
            $matchedRoute = self::routeFound(self::$stack, $incomingPath, $incomingMethod);
            if ($matchedRoute !== null) {
                // If a route is found, execute the middleware and the route handler
                $matchedRoute->run($errorHandler);
            } else {
                // If no route is found, serve the static file
                self::serveStaticFile($fullPathToStaticFolder, $incomingPath);
            }
        } catch (Exception $e) {
            // depending on xmlhttprequest to determine if request is ajax ($request->is_ajax)
            $errorHandler($e, $request->is_ajax);
        }
    }

    private static function routeFound(array $stack, string $incomingPath, string $incomingMethod): ?Route {
      foreach ($stack as $path => $router) {
          if (strpos($incomingPath, $path) === 0) {
              $currentRouter = $stack[$path];
              foreach ($currentRouter->getStack() as $route) {
                  if ($route->path === $incomingPath && $route->method === $incomingMethod) {
                      return $route; // return the route if found
                  }
              }
              // If no route matches, return null
              return null;
          }
      }
      // If no app stack key starts with the incoming path, return null
      return null;
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
                "serveStaticFile | ".$fullPathToStaticFolder.$requestPath,
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
            // Set content type for javascript files
            if (strpos($fullFilePath, '.js') !== false || strpos($fullFilePath, '.mjs') !== false) {
              header('Content-Type: application/javascript');
            }
            // Output the file contents
            readfile($fullFilePath);
        } else {
            // file is not found or not readable
            $errorUtils = new ErrorUtils();
            // Throw a custom exception which ll be caught by the error handler when the app is bootstrapped
            throw $errorUtils->customError(
                "FileNotFoundException",
                "App",
                "serveStaticFile | ".$fullFilePath,
                "Not Found",
                404,
                debug_backtrace()
            );
        }
    }

    /**
     * Retrieves an instance of Request.
     * (Factory method for manufacturing incoming request data bearer Class instance)
     *
     * @return Request The Request instance.
     */
    public static function request(): Request {
        return new Request();
    }

    /**
     * Handles the response by sending the appropriate headers and response  or file 
     * based on ajax and non-ajax request (check renderYourViewsFile for non-ajax request)
     *
     * this way we can pass data only in private scope to the view
     * ensuring the global variables are not polluted or exposed to the view
     *
     * @param mixed  $response         The response to be sent.
     * @param callable $renderYourViewsFile The function to render your views file.
     * @return void
     * @throws Exception If the response is not a valid type. (type hinting is used to ensure the response is a valid type)
     *
     */
    public static function handleResponse(
        $response = null,
        callable $renderYourViewsFile = null,
        ): void {
        if (!is_null($renderYourViewsFile) && is_callable($renderYourViewsFile)) {
             $renderYourViewsFile($response);
        } else {
            if(is_string($response)) {
                http_response_code(200);
                echo $response;
            } else if(is_array($response)) {
                http_response_code(200);
                echo json_encode($response);
            } else if(is_object($response)) {
                http_response_code(200);
                echo json_encode($response);
            } else {
                throw new Exception('Invalid response type.');
            }
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


