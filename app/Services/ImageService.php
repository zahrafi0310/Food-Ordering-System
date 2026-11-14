<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageService
{
    public function store(UploadedFile $file, string $directory) : string
    {
        // Generate a unique name for the uploaded file
        $fileName = $file->hashName();

        // Store the file with the new name
        return $file->storeAs($directory, $fileName);
    }

    public function delete(?string $path) : void
    {
        if ($path) {
            Storage::delete($path);
        }
    }

    public function replace(UploadedFile $file, string $directory, ?string $oldPath) : string
    {
        $this->delete($oldPath);

        return $this->store($file, $directory);
    }
}