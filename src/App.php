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
                error_log('Non-callable item passed.');
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
     *  @param array    $extraMimeTypes        An array of extra MIME types to be served.
     */
    public static function bootstrap(
        callable $errorHandler,
        string $fullPathToStaticFolder,
        array $extraMimeTypes = []
        ): void {
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
                self::serveStaticFile($fullPathToStaticFolder, $incomingPath, $extraMimeTypes);
            }
        } catch (Exception $e) {
            // depending on xmlhttprequest to determine if request is ajax ($request->is_ajax)
            $errorHandler($e, $request->is_ajax);
        }
    }

    /**
     * Checks if a route is found in the application stack.
     *
     * @param Router[] $stack          The application stack.
     * @param string   $incomingPath   The incoming request path.
     * @param string   $incomingMethod The incoming request method.
     * @return Route|null The matched route if found, or null if no route is found.
     */
    private static function routeFound(array $stack, string $incomingPath, string $incomingMethod): ?Route {
      // Loop through the application stack (routers)
      foreach ($stack as $path => $router) {
          if (strpos($incomingPath, $path) === 0) {
            // router matched, continue to routes inside this router
              $currentRouter = $stack[$path];
              // loop through the routes in the current router (routes)
              foreach ($currentRouter->getStack() as $route) {
                  $routePath = $route->path;
                  $routeMethod = $route->method;
                  if ($routePath === $incomingPath && $routeMethod === $incomingMethod) {
                    // route matched, return route breaking loop and function
                    return $route;
                  }
                  // If route not matched, let it continue to the next route in current router
              }
              // If no route matched in current router, let it continue to the next router
          }
          // If current router not matched, let it continue to the next router
      }
      // If no router matched, return null breaking the loop and function
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
     string $requestPath,
     array $extraMimeTypes = []
    ): void {
      // Check if the request path has an extension
      if (!pathinfo($requestPath, PATHINFO_EXTENSION)) {
        // If no extension, file is not found
        $errorUtils = new ErrorUtils();
        $filePath = htmlspecialchars($requestPath);
        error_log("File not found: $filePath");
        // Throw a custom exception
        throw $errorUtils->customError(
            "FileNotFoundException",
            "App/bootstrap/notFound/so_serveStaticFile",
            "serveStaticFile_too_not_found | $filePath",
            "Not Found",
            404,
            debug_backtrace()
        );
      }
      $fullFilePath = "$fullPathToStaticFolder$requestPath";
      // Check if the file exists and is readable
      if (file_exists($fullFilePath) && is_readable($fullFilePath)) {
        // Get the file extension
        $fileExtension = strtolower(pathinfo($fullFilePath, PATHINFO_EXTENSION));
        
        // Set the appropriate Content-Type header
        $mimeTypes = array_merge(
            array(
            'html' => 'text/html',
            'css' => 'text/css',
            'min.css' => 'text/css',
            'js' => 'application/javascript',
            'min.js' => 'application/javascript',
            'module.js' => 'application/javascript', 
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'ico' => 'image/x-icon',
            'svg' => 'image/svg+xml',
            'pdf' => 'application/pdf',
            'zip' => 'application/zip',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'mp3' => 'audio/mpeg',
            'mp4' => 'video/mp4',
            // Add more file extensions and MIME types as needed
        ),
        $extraMimeTypes 
        );
        $contentType = isset($mimeTypes[$fileExtension]) ? $mimeTypes[$fileExtension] : 'text/plain';
        // Set the appropriate Content-Type header
        header("Content-Type: $contentType");
        // Output the file contents
        readfile($fullFilePath);
      }
      else {
        // File not found or not readable
        $errorUtils = new ErrorUtils();
        $filePath = htmlspecialchars($fullFilePath);
        error_log("File not found: $filePath");
        // Throw a custom exception
        throw $errorUtils->customError(
            "FileNotFoundException",
            "App/bootstrap/notFound/so_serveStaticFile",
            "serveStaticFile_too_not_found | $filePath",
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
        try{
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
                error_log('Invalid response type.');
                throw new Exception('Invalid response type.');
            }
        }
       } catch(Exception $e) {
              error_log('error in handleResponse: ' . $e->getMessage());
            throw new Exception('error in handleResponse: ' . $e->getMessage());
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



