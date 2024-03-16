<?php
namespace Bibek8366\MyPhpApp\Helpers;
class SuccessUtils {
    public static function sendResponse(
        mixed $response = null,
        ?callable $renderView = null,
        ?string $filePath = null,
        int $statusCode = 200,
    ): void {
        // success status code (200 done, 201 created, etc)
        http_response_code($statusCode);
        if ($response !== null && is_array($response)) {
            header('Content-Type: application/json');
            echo json_encode($response);
        }
        elseif ($response !== null && is_string($response)) {
            echo $response;
        }
        elseif ($renderView !== null) {
            $renderView($response);
        }
        elseif ($filePath !== null && is_readable($filePath)) {
            // Set Content-Type header for file download
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
            // Send file contents
            readfile($filePath);
        }
        else {
            $errorUtils = new ErrorUtils();
            throw $errorUtils->customError(
                    'Invalid response type',
                    'App/SuccessUtils',
                    'sendResponse',
                    500,
                    'Internal Server Error',
                    debug_backtrace()
                );
        }
    }

}



