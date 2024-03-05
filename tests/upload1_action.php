<?php
require_once 'testClasses/FileUtils.php';

// Retrieve paths of files automatically stored by the server from a POST field
$fieldNames = ['file', 'gg'];

$fileUtils = new FileUtils();
foreach ($fieldNames as $fieldName) {
    try {
        $filePaths = $fileUtils->getPathsOfFilesAutoTempStoredByServerFromPOSTField($fieldName);
        $fileUtils->uploadToFolder($filePaths, __DIR__ . '/uploads');
        echo 'File(s) uploaded successfully';
    } catch (Exception $e) {
        echo 'Error: ' . $e->getMessage();
    }
}

