<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ImageUploadService
{
    /**
     * Stocke une image en JPEG (compatible navigateurs) sous storage/app/public.
     */
    public function storeAsJpeg(UploadedFile $file, string $directory, int $maxWidth = 1200): string
    {
        $mime = (string) $file->getMimeType();
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (! in_array($mime, $allowed, true)) {
            throw new InvalidArgumentException(
                'Format non supporté ('.$mime.'). Utilisez JPG, PNG, GIF ou WebP — pas HEIC/HEIF.'
            );
        }

        if (! extension_loaded('gd')) {
            return $file->store($directory, 'public');
        }

        $raw = @file_get_contents($file->getRealPath());
        if ($raw === false) {
            throw new InvalidArgumentException('Impossible de lire le fichier image.');
        }

        $source = @imagecreatefromstring($raw);
        if ($source === false) {
            throw new InvalidArgumentException(
                'Image illisible ou format non supporté. Exportez en JPG ou PNG.'
            );
        }

        $width = imagesx($source);
        $height = imagesy($source);

        if ($width > $maxWidth) {
            $newHeight = (int) round($height * ($maxWidth / $width));
            $resized = imagecreatetruecolor($maxWidth, $newHeight);
            imagecopyresampled($resized, $source, 0, 0, 0, 0, $maxWidth, $newHeight, $width, $height);
            imagedestroy($source);
            $source = $resized;
        }

        $filename = $directory.'/'.Str::uuid()->toString().'.jpg';
        $absolute = Storage::disk('public')->path($filename);
        Storage::disk('public')->makeDirectory($directory);

        imagejpeg($source, $absolute, 85);
        imagedestroy($source);

        return $filename;
    }
}
