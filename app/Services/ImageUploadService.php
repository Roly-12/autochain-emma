<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ImageUploadService
{
    /**
     * Convertit puis stocke une image JPEG sur le disque média configuré.
     */
    public function storeAsJpeg(UploadedFile $file, string $directory, int $maxWidth = 1200): string
    {
        $disk = (string) config('filesystems.media_disk', 'public');
        $mime = (string) $file->getMimeType();
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (! in_array($mime, $allowed, true)) {
            throw new InvalidArgumentException(
                'Format non supporté ('.$mime.'). Utilisez JPG, PNG, GIF ou WebP — pas HEIC/HEIF.'
            );
        }

        if (! extension_loaded('gd')) {
            throw new InvalidArgumentException('L’extension PHP GD est requise pour traiter les images.');
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
        ob_start();
        $encoded = imagejpeg($source, null, 85);
        $jpeg = ob_get_clean();
        imagedestroy($source);

        if (! $encoded || ! is_string($jpeg)) {
            throw new InvalidArgumentException('Impossible d’encoder l’image en JPEG.');
        }

        try {
            $stored = Storage::disk($disk)->put($filename, $jpeg, [
                'ContentType' => 'image/jpeg',
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            throw new InvalidArgumentException(
                'Impossible d’enregistrer l’image sur le stockage configuré.',
                previous: $exception
            );
        }

        if (! $stored) {
            throw new InvalidArgumentException('Impossible d’enregistrer l’image.');
        }

        return $filename;
    }
}
