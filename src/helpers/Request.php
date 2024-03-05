<?php
namespace Bibek8366\MyPhpApp\Helpers;
/**
 * Represents an HTTP request received by the server.
 */
class Request {
    public string $path; // The requested path
    public string $method; // The HTTP method of the request
    public array $params; // Query parameters of the request
    public array $body; // Body of the request
    public bool $is_ajax; // Indicates if the request is an AJAX request

    /**
     * Constructs a Request object based on the current HTTP request.
     */
    public function __construct() {
        $this->path = explode('?', $_SERVER['REQUEST_URI'])[0]; // Extract the path from the request URI
        $this->method = $_SERVER['REQUEST_METHOD']; // Retrieve the HTTP method of the request
        $this->params = !empty($_GET) ? $_GET : []; // Retrieve the query parameters of the request
        $this->body = !empty($_POST) ? $_POST : []; // Retrieve the body of the request
        // Set XMLHttpRequest header during AJAX request creation in client-side
        // to ensure accurate AJAX detection ----------------------------------------------------------
        $this->is_ajax = 'xmlhttprequest' == strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
    }
}



