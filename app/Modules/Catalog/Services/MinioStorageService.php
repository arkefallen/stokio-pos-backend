<?php

namespace App\Modules\Catalog\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MinioStorageService
{
    protected string $disk = 'minio';
    protected string $productImagesPath = 'products';

    /**
     * Upload product image to MinIO
     *
     * @param UploadedFile $file
     * @param string|null $oldPath Optional old image path to delete
     * @return string The stored file path
     */
    public function uploadProductImage(UploadedFile $file, ?string $oldPath = null): string
    {
        // Delete old image if exists
        if ($oldPath) {
            $this->deleteFile($oldPath);
        }

        // Generate unique filename
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $this->productImagesPath . '/' . $filename;

        // Store file
        Storage::disk($this->disk)->put($path, file_get_contents($file), 'public');

        return $path;
    }

    /**
     * Get a temporary signed URL for a file
     *
     * @param string $path
     * @param int $expirationMinutes
     * @return string|null
     */
    public function getSignedUrl(string $path, int $expirationMinutes = 60): ?string
    {
        if (!$path || !Storage::disk($this->disk)->exists($path)) {
            return null;
        }

        return Storage::disk($this->disk)->temporaryUrl(
            $path,
            now()->addMinutes($expirationMinutes)
        );
    }

    /**
     * Get public URL for a file (if bucket is public)
     *
     * @param string $path
     * @return string|null
     */
    public function getPublicUrl(string $path): ?string
    {
        if (!$path) {
            return null;
        }

        return Storage::disk($this->disk)->url($path);
    }

    /**
     * Delete a file from storage
     *
     * @param string $path
     * @return bool
     */
    public function deleteFile(string $path): bool
    {
        if (!$path) {
            return false;
        }

        return Storage::disk($this->disk)->delete($path);
    }

    /**
     * Check if file exists
     *
     * @param string $path
     * @return bool
     */
    public function exists(string $path): bool
    {
        return Storage::disk($this->disk)->exists($path);
    }
}
