<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

if (! function_exists('processLogoImage')) {
    /**
     * Process and upload organisation logo
     *
     * @return string|bool Path of saved file or false on failure
     *
     * @throws Exception
     */
    function processLogoImage(UploadedFile $file, string $organisationName, string $folder): bool|string
    {
        try {
            // Generate snake_case filename with timestamp
            $fileName = Str::slug($organisationName) . '_' . now()->timestamp . '.webp';

            // Define storage path
            $directory = storage_path('app/public/' . $folder . '/');
            $storagePath = $directory . $fileName;

            // Ensure directory exists
            if (! File::isDirectory($directory)) {
                File::makeDirectory($directory, 0755, true);
            }
            // Keep logos crisp enough for PDF output while still optimizing size.
            $encodedImage = Image::read($file)
                ->scaleDown(height: 400)
                ->toWebp(90);

            // Save the image to the storage path
            $encodedImage->save($storagePath);

            // Return the relative path for storage access
            return 'storage/' . $folder . '/' . $fileName;
        } catch (\Exception $e) {
            // Log the error if needed
            throw new Exception('Error Processing Logo: ' . $e->getMessage());
        }
    }
}
