<?php
namespace Bibek8366\MyPhpApp\Helpers;
use Exception;
class ViewConfig {
    private string $fullPathToViewsDir;
    public function __construct(string $fullPathToViewsDir) {
        $this->fullPathToViewsDir = $fullPathToViewsDir;
    }
    public function render(string $furtherPathToView, array $data = []): void {
        extract($data); /* Extracts the data array into variables named as keys of the array */
        $filePath = $this->fullPathToViewsDir . $furtherPathToView . '.php';
        // check if the file exists and readable
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new Exception("View file not found or not readable");
        }
        require_once $filePath;
    }

}


