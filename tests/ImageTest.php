<?php
declare(strict_types=1);

namespace Eightfold\Image\Tests;

use PHPUnit\Framework\TestCase;

use Eightfold\Image\Image;

use Eightfold\Image\Errors\Image as ImageError;

class ImageTest extends TestCase
{
    public function tearDown(): void
    {
        $delete = [
            __DIR__ . '/test-files/8fold-jewel-copy.jpg',
            __DIR__ . '/test-files/subdir/8fold-jewel-copy.jpg',
            __DIR__ . '/test-files/8fold-jewel-resized.jpg',
            __DIR__ . '/test-files/subdir'
        ];

        foreach ($delete as $d) {
            if (is_file($d)) {
                unlink($d);
            }

            if (is_dir($d)) {
                rmdir($d);
            }
        }
        parent::tearDown();
    }

    /**
     * @test
     */
    public function can_scale(): void
    {
        $expected = 37;

        $sut = Image::atLocalPath(__DIR__ . '/test-files/8fold-jewel-small.jpg')
            ->scale(
                0.5,
                __DIR__ . '/test-files/8fold-jewel-resized.jpg'
            );

        $result = $sut->width();

        $this->assertSame(
            $expected,
            $result
        );

        $expected = 66;

        $result = $sut->height();

        $this->assertSame(
            $expected,
            $result
        );
    }

    /**
     * @test
     */
    public function can_scale_width_and_height(): void
    {
        $expected = 500;

        $result = Image::atLocalPath(__DIR__ . '/test-files/8fold-jewel-small.jpg')
            ->scaleToWidth(
                $expected,
                __DIR__ . '/test-files/8fold-jewel-resized.jpg'
            )->width();

        $this->assertSame(
            $expected,
            $result
        );

        $result = Image::atLocalPath(__DIR__ . '/test-files/8fold-jewel-small.jpg')
            ->scaleToHeight(
                $expected,
                __DIR__ . '/test-files/8fold-jewel-resized.jpg'
            )->height();

        $this->assertTrue(
            $result <= $expected + 1 and $result >= $expected - 1
        );
    }

    /**
     * @test
     */
    public function can_save_file(): void
    {
        Image::atLocalPath(__DIR__ . '/test-files/8fold-jewel-small.jpg')
            ->didSave(__DIR__ . '/test-files/8fold-jewel-copy.jpg');

        $this->assertTrue(
            file_exists(__DIR__ . '/test-files/8fold-jewel-copy.jpg')
        );

        Image::atLocalPath(__DIR__ . '/test-files/8fold-jewel-small.jpg')
            ->didSave(__DIR__ . '/test-files/subdir/8fold-jewel-copy.jpg');

        $this->assertTrue(
            file_exists(__DIR__ . '/test-files/subdir/8fold-jewel-copy.jpg')
        );
    }

    /**
     * @test
     */
    public function can_initialize_jpeg(): void
    {
        $result = Image::atLocalPath(__DIR__ . '/test-files/8fold-jewel-small.jpg');

        $this->assertNotNull(
            $result
        );
    }

    /**
     * @test
     */
    public function unsupported_mime_type(): void
    {
        $result = Image::atLocalPath(__DIR__ . '/test-files/plain-text.txt');

        $this->assertTrue(
            $result === ImageError::UnsupportedMimeType
        );
    }

    /**
     * @test
     */
    public function file_not_found(): void
    {
        $result = Image::atLocalPath(__DIR__ . '/image.png');

        $this->assertTrue(
            $result === ImageError::FileNotFound
        );
    }
}
