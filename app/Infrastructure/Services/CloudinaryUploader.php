<?php

namespace App\Infrastructure\Services;

use App\Application\Ports\ImageUploaderInterface;
use Cloudinary\Cloudinary;

class CloudinaryUploader implements ImageUploaderInterface
{
    private function client(): Cloudinary
    {
        $url = getenv('CLOUDINARY_URL');
        if ($url) {
            return new Cloudinary($url);
        }

        $cloud  = getenv('CLOUDINARY_CLOUD_NAME');
        $key    = getenv('CLOUDINARY_API_KEY');
        $secret = getenv('CLOUDINARY_API_SECRET');

        return new Cloudinary("cloudinary://{$key}:{$secret}@{$cloud}");
    }

    public function uploadImage(string $tempPath, string $folder = 'images'): string
    {
        $cloudinary = $this->client();

        $result = $cloudinary->uploadApi()->upload($tempPath, [
            'folder'        => 'jnv/' . $folder,
            'resource_type' => 'image',
        ]);

        return $result['secure_url'];
    }

    public function uploadPdf(string $tempPath, string $folder = 'pdfs'): string
    {
        $cloudinary = $this->client();
        $filename   = pathinfo($tempPath, PATHINFO_FILENAME);

        $result = $cloudinary->uploadApi()->upload($tempPath, [
            'folder'          => 'jnv/' . $folder,
            'public_id'       => $filename,
            'resource_type'   => 'raw',
            'use_filename'    => true,
            'unique_filename' => true,
        ]);

        return $result['secure_url'];
    }
}
