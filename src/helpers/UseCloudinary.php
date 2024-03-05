<?php
namespace MyPhpApp\Helpers;

include 'vendor/autoload.php';

// Use the Configuration class 
use Cloudinary\Cloudinary;
use Exception;

class UseCloudinary {
    /**
     * Initializes Cloudinary with the provided credentials.
     *
     * @param string $cloudinaryName    The Cloudinary account name.
     * @param string $cloudinaryApiKey  The Cloudinary API key.
     * @param string $cloudinaryApiSecret  The Cloudinary API secret key.
     * @return Cloudinary  An instance of Cloudinary.
     */
    public function initializeCloudinary(
        string $cloudinaryName,
        string $cloudinaryApiKey,
        string $cloudinaryApiSecret
    ): Cloudinary {
        // Configure an instance of your Cloudinary cloud
       $cloudinary = new Cloudinary(
            array(
                "cloud_name" => $cloudinaryName,
                "api_key" => $cloudinaryApiKey,
                "api_secret" => $cloudinaryApiSecret
            )
        );
        return $cloudinary;
    }

    /**
     * Uploads files to Cloudinary.
     *
     * @param Cloudinary $cloudinary        An instance of Cloudinary.
     * @param array  $filePaths             An array of file paths to be uploaded.
     * @param string $cloudinaryFolder      The Cloudinary folder to upload the files to.
     * @throws Exception                    If file upload to Cloudinary fails.
     */
    public function uploadToCloudinary(
        Cloudinary $cloudinary,
        array $filePaths,
        string $cloudinaryFolder
    ): void {
        foreach($filePaths as $filePath) {
            try {
              // Upload file to Cloudinary
              $cloudinary->uploadApi()->upload($filePath, array(
                "folder" => $cloudinaryFolder,
                "use_filename" => true,
                "unique_filename" => true
              ));
            } catch (Exception $e) {
              // caller should handle the exception ------------
              throw new Exception("Cloudinary upload error: " . $e->getMessage());
            }
        }
    }

    /**
     * Deletes files from Cloudinary.
     *
     * @param Cloudinary $cloudinary         An instance of Cloudinary.
     * @param array  $fileUrls               An array of file URLs to be deleted from Cloudinary.
     * @throws Exception                     If file deletion from Cloudinary fails.
     */
    public function deleteFromCloudinary(Cloudinary $cloudinary, array $fileUrls) {
        // Extract public IDs from file URLs
        $publicIds = $this->getPublicIds($fileUrls);
        try {
            // Delete files from Cloudinary
            $cloudinary->adminApi()->deleteAssets($publicIds);
        } catch (Exception $e) {
            // caller should handle the exception ------------
            throw new Exception("Cloudinary delete error: " . $e->getMessage());
        }
    }

    // Private function to extract public IDs from file URLs
    private function getPublicIds($fileUrls): array {
        $publicIds = array();
        foreach ($fileUrls as $fileUrl) {
            $publicIds[] = basename(
                parse_url($fileUrl,PHP_URL_PATH),
                '.' . pathinfo(parse_url($fileUrl, PHP_URL_PATH),
                    PATHINFO_EXTENSION
                ));
        }
        return $publicIds;
    }
}

