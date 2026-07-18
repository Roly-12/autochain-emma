<?php

namespace Tests\Unit;

use App\Services\ImageUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageUploadServiceTest extends TestCase
{
    public function test_it_stores_a_jpeg_on_the_configured_media_disk(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD est requis pour ce test.');
        }

        Storage::fake('remote-media');
        config(['filesystems.media_disk' => 'remote-media']);

        $path = (new ImageUploadService())->storeAsJpeg(
            UploadedFile::fake()->image('avatar.png', 320, 180),
            'avatars',
            200
        );

        $this->assertStringStartsWith('avatars/', $path);
        $this->assertStringEndsWith('.jpg', $path);
        Storage::disk('remote-media')->assertExists($path);
        $this->assertSame(
            'image/jpeg',
            (new \finfo(FILEINFO_MIME_TYPE))->buffer(Storage::disk('remote-media')->get($path))
        );
    }
}
