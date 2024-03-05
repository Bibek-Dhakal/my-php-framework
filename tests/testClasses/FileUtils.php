<?php
/**
 * Utility class for handling file operations.
 */
class FileUtils {

    /**
     * Retrieves paths of files automatically stored by the server from a POST field.
     *
     * @param string $fieldName       The name of the POST field containing uploaded files.
     * @return array                  An array of file paths.
     * @throws Exception              If no file is provided for the specified field.
     */
    public function getPathsOfFilesAutoTempStoredByServerFromPOSTField(string $fieldName): array {
        if (!isset($_FILES[$fieldName])) {
            throw new Exception("No file provided for field: $fieldName");
        }
        $filePaths = array();
        if (is_string($_FILES[$fieldName]['tmp_name'])) {
            // Single file from input field
            $filePaths[] = $_FILES[$fieldName]['tmp_name'];
        } else {
            // Multiple files from input field
            foreach ($_FILES[$fieldName]['tmp_name'] as $index => $tmpName) {
                $filePaths[] = $tmpName;
            }
        }
        return $filePaths;
    }

    /**
     * Uploads files to the specified folder.
     *
     * @param array  $filePaths       An array of file paths to be uploaded.
     * @param string $FolderFullPath  The full path to the destination folder.
     * @throws Exception              If file upload fails.
     */
    public function uploadToFolder(array $filePaths, string $FolderFullPath): void {
        // Check if uploads folder exists, if not, create it
        if (!is_dir($FolderFullPath)) {
          mkdir($FolderFullPath, 0777, true); // Create folder with full permissions
        }
        foreach ($filePaths as $filePath) {
            $filename = basename($filePath);
            $destination = $FolderFullPath . '/' . $filename;
            if (!move_uploaded_file($filePath, $destination)) {
                throw new Exception("Failed to move file: $filename");
            }
        }
    }

    /**
     * Deletes files from the specified folder.
     *
     * @param array  $filenames       An array of filenames to be deleted.
     * @param string $folderFullPath  The full path to the folder containing the files.
     * @throws Exception              If file deletion fails.
     */
    public function deleteFromFolder(array $filenames, string $folderFullPath): void {
        foreach ($filenames as $filename) {
            $filePath = $folderFullPath . '/' . $filename;
            if (!unlink($filePath)) {
                throw new Exception("Failed to delete file: $filename");
            }
        }
    }

    /**
     * Deletes all files from the specified folder.
     *
     * @param string $folderFullPath  The full path to the folder.
     * @throws Exception              If file deletion fails or the folder doesn't exist.
     */
    public function deleteAllFilesFromTempFolder(string $folderFullPath): void {
        if (!is_dir($folderFullPath)) {
            throw new Exception("Folder doesn't exist: {$folderFullPath}");
        }
        $handle = opendir($folderFullPath);
        while (false !== ($file = readdir($handle))) {
            if ($file != "." && $file != "..") {
                $filePath = $folderFullPath . '/' . $file;
                if (is_file($filePath)) {
                    if (!unlink($filePath)) {
                        throw new Exception("Failed to delete file: $file");
                    }
                }
            }
        }
        closedir($handle);
    }

    /**
     * Returns an instance of the UseCloudinary class for interacting with Cloudinary services.
     *
     * @return UseCloudinary  An instance of the UseCloudinary class.
     */
    /*public function useCloudinary(): UseCloudinary {
        return new UseCloudinary();
    }*/

}


