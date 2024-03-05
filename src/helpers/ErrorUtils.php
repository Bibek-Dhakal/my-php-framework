<?php
namespace Bibek8366\MyPhpApp\Helpers;
use Exception;
use Error;
/**
ErrorUtils class provides methods to handle errors and create custom error objects.
Note: If using ajax, must set the header 'HTTP_X_REQUESTED_WITH' to 'xmlhttprequest' in the request.
*/
class ErrorUtils {
    /**
     * Creates a custom error object.
     *
     * @param string $message        The error message.
     * @param string $service        The service where the error occurred.
     * @param string $method         The method where the error occurred.
     * @param string $publicMessage The public-facing error message.
     * @param int    $statusCode     The HTTP status code associated with the error.
     * @param array  $backtrace      The backtrace information associated with the error.
     * @return CustomError          The custom error object.
     */
    public function customError(
        string $message,
        string $service,
        string $method,
        string $publicMessage,
        int $statusCode,
        array $backtrace
    ): CustomError {
        return new CustomError(
            $message,
            $service,
            $method,
            $publicMessage,
            $statusCode,
            $backtrace
        );
    }

    /**
     * Handles errors by formatting the error response and sending appropriate HTTP status code.
     *
     * @param Error | Exception $e   The exception object.
     * @param string    $env The environment mode (e.g., 'prod', 'dev').
     */
    public function handleError(
        Error | Exception $e,
        string $env = 'dev',
        bool $is_ajax = false,
        callable $renderYourProdErrorPagesForNonAjaxREquest = null
        ): void {
        $errorResponse = self::formatErrorResponse($e, $env);
        self::respondWithError($errorResponse, $e, $is_ajax, $renderYourProdErrorPagesForNonAjaxREquest);
        // instance of Error or Exception can also be passed as argument
        // Error is the base class for all internal PHP errors.
        // Exception is the base class for all user-defined exceptions.
        // CustomError which is instance of class that extends exception also be passed as argument.
    }

    private function formatErrorResponse(Error | Exception $e, string $env): array {
        $errorResponse = [
            "success" => false,
            "statusCode" => 500,
            "message" => 'Internal Server Error',
        ];
        if ($e instanceof CustomError) {
            $errorResponse['statusCode'] = $e->getStatusCode();
            $errorResponse['message'] = $e->getPublicMessage();
        }
        if ($env !== 'prod' && $env !== 'production') {
            // In dev mode, show the full error message
            $errorResponse['detailed_message'] = $e->getMessage();
            $errorResponse['stackTrace'] = $e->getTrace();
            if($e instanceof CustomError) {
                $errorResponse['backTrace'] = $e->getBackTrace();
            }
        }
        return $errorResponse;
    }

    private function respondWithError(
        array $errorResponse,
        Error | Exception $e,
        bool $is_ajax,
        callable $renderYourProdErrorPageForNonAjaxREquest = null
        ): void {
       http_response_code($errorResponse['statusCode']); // set the HTTP status code
        if (isset($errorResponse['stackTrace'])) {
            // dev mode
            $errorResponse['backTrace'] = $errorResponse['backTrace'] ?? debug_backtrace();
            if($is_ajax) {
                /* in dev mode, minified payload is not must, so used JSON_PRETTY_PRINT for better readability */
                echo json_encode($errorResponse, JSON_PRETTY_PRINT);
            } else {
                self::devErrorStyles();
                echo "<h2>Request</h2>";
                echo "URI: ".$_SERVER['REQUEST_URI']."<br>";
                echo "METHOD: ".$_SERVER['REQUEST_METHOD']."<br>";
                echo "Query Params: ".json_encode($_GET, JSON_PRETTY_PRINT)."<br>";
                echo "Body: ".json_encode($_POST, JSON_PRETTY_PRINT)."<br><br>";
                echo "Note:<br>";
                echo "In query params, 1st key: value is uri, others are query params<br>";
                echo "For GET request, body is empty";
                echo "<h2>Error</h2>";
                echo "<pre><code>".json_encode($errorResponse, JSON_PRETTY_PRINT)."</code></pre>";
                echo "<h2>Stacktrace as string</h2>";
                echo "<pre>{$e->getTraceAsString()}</pre>";
            }
        } else {
            // prod mode
            if ($is_ajax) {
              echo json_encode($errorResponse);
            }
            else {
              if($renderYourProdErrorPageForNonAjaxREquest !== null
              && is_callable($renderYourProdErrorPageForNonAjaxREquest)) {
               call_user_func($renderYourProdErrorPageForNonAjaxREquest, $errorResponse);
              }
              else {
               echo "<h1>{$errorResponse['statusCode']}</h1>";
               echo "<p>{$errorResponse['message']}</p>";
              }
            }
        }
    }

    private function devErrorStyles(): void {
        echo "<style>\n" .
             "  pre {\n" .
             "    width: fit-content;\n" .
             "    min-width: calc(100% - 20px);\n" .
             "    padding: 10px;\n" .
             "    margin: 0;\n" .
             "    margin-bottom: 10px;\n" .
             "    overflow: auto;\n" .
             "    overflow-y: hidden;\n" .
             "    font-size: 12px;\n" .
             "    line-height: 20px;\n" .
             "    background: #efefef;\n" .
             "    border: 1px solid #777;\n" .
             "  }\n" .
             "  pre code {\n" .
             "    padding: 10px;\n" .
             "    color: #333;\n" .
             "  }\n" .
             "</style>\n";
    }

}

class CustomError extends Exception {
    protected string $publicMessage;
    protected int $statusCode;
    protected array $backtrace;

    public function __construct(
     string $message,
     string $service,
     string $method,
     string $publicMessage,
     int $statusCode,
     array $backtrace
    ) {
        $detailedMessage = "$message in $service::$method";
        parent::__construct($detailedMessage);
        $this->publicMessage = $publicMessage;
        $this->statusCode = $statusCode;
        $this->backtrace = $backtrace;
    }

    public function getStatusCode(): int {
        return $this->statusCode;
    }

    public function getPublicMessage(): string {
        return $this->publicMessage;
    }

    public function getBacktrace(): array {
        return $this->backtrace;
    }
}



