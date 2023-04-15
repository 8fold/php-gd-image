<?php
declare(strict_types=1);

namespace Eightfold\Image;

use GDImage;

use Eightfold\Image\Errors\Environment as EnvironmentError;
use Eightfold\Image\Errors\Image as ImageError;

use Eightfold\Image\ImageFormats;

class Image
{
    /**
     * @var array<int|string, int|string>
     */
    private array $imageSizeInfo = [];

    /**
     * Will overwrite image file at path.
     *
     * @param string $url Use HTTP or FTP
     * @param string $destination Destination
     *
     * @return self
     */
    public static function fromUrlToLocalPath(
        string $url,
        string $destination
    ): self|ImageError|EnvironmentError {
        $parts = explode('/', $destination);
        $last  = array_pop($parts);
        if (str_contains($last, '.') === false) {
            $file = self::filenameFromUrl($url);
            if (is_string($file) === false) {
                return $file;
            }
            $destination = $destination . '/' . $file;
        }

        $copied = self::copiedUrlToLocalpath($url, $destination);
        if (is_bool($copied) === false) {
            return $copied;
        }

        if ($copied === false) {
            return ImageError::FailedToCopyFromUrl;
        }

        return self::atLocalPath($destination);
    }

    public static function atLocalPath(string $destination): self|ImageError
    {
        if (is_file($destination) === false) {
            return ImageError::FileNotFound;
        }

        $mimetype = mime_content_type($destination);
        if (
            in_array($mimetype, ImageFormats::SUPPORTED_MIME_TYPES) === false or
            $mimetype === false
        ) {
            return ImageError::UnsupportedMimeType;
        }

        $image = match ($mimetype) {
            default => imagecreatefromjpeg($destination)
        };
        if ($image === false) {
            return ImageError::UnsupportedMimeType;
        }

        return self::fromImage($image, $destination);
    }

    public static function fromImage(GDImage $image, string $destination): self
    {
        return new self($image, $destination);
    }

    public static function copiedUrlToLocalPath(
        string $from,
        string $to,
        bool $makeDirectory = true
    ): bool|EnvironmentError {
        if (
            self::isHttpOrFtp($from) and
            filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN) === false
        ) {
            return EnvironmentError::AllowUrlFOpenNotEnabled;
        }

        if (self::isHttpOrFtp($to)) {
            return EnvironmentError::CopyDestinationMustBeLocal;
        }

        if (file_exists($to) === false) {
            if (
                $makeDirectory and
                self::didmakeDirectoryFor($to) === false
            ) {
                return EnvironmentError::FailedToCreateDestinationDirectory;
            }
        }

        return copy($from, $to);
    }

    public static function filenameFromUrl(string $url): string|EnvironmentError
    {
        $url = parse_url($url);
        if ($url === false) {
            return EnvironmentError::FailedToParseUrl;
        }

        if (array_key_exists('path', $url) === false) {
            return EnvironmentError::UrlPathNotFound;
        }

        $path  = $url['path'];
        $parts = explode('/', $path);

        return array_pop($parts);
    }

    private static function didMakeDirectoryFor(string $destination): bool
    {
        $dir = self::directoryFromFilename($destination);
        if (is_dir($dir)) {
            return true;
        }

        return mkdir($dir, 0777, true);
    }

    private static function directoryFromFilename(string $destination): string
    {
        $dir = explode('/', $destination);
        array_pop($dir);
        return implode('/', $dir);
    }

    private static function isHttpOrFtp(string $path): bool
    {
        return str_starts_with($path, 'http') or
            str_starts_with($path, 'ftp') or
            str_starts_with($path, 'sftp');
    }

    final private function __construct(
        private readonly GDImage $image,
        private readonly string $destination
    ) {
    }

    public function image(): GDImage
    {
        return $this->image;
    }

    public function destination(): string
    {
        return $this->destination;
    }

    public function filename(): string
    {
        $parts = explode('/', $this->destination());
        return array_pop($parts);
    }

    public function scale(float $scale, string $destination): self|ImageError
    {
        $tWidth = intval($this->width() * $scale);
        $new = imagescale($this->image(), $tWidth);
        if ($new === false) {
            return ImageError::FailedToScaleImage;
        }

        $image = self::fromImage($new, $destination);
        if ($image->didSave($destination) === false) {
            return ImageError::FailedToSaveAfterScaling;
        }

        return $image;
    }

    public function scaleToWidth(int $width, string $destination): self|ImageError
    {
        $scale = $width / $this->width();
        return $this->scale($scale, $destination);
    }

    public function scaleToHeight(int $height, string $destination): self|ImageError
    {
        $scale = $height / $this->height();
        return $this->scale($scale, $destination);
    }

    public function didSave(string $destination, bool $makeDirectory = true): bool
    {
        if (self::didMakeDirectoryFor($destination) === false) {
            return false;
        }
        return imagejpeg($this->image(), $destination);
    }

    /**
     * @return array<int|string, int|string>
     */
    private function getImageSize(): array
    {
        if (count($this->imageSizeInfo) === 0) {
            $size = getimagesize($this->destination());
            if ($size === false) {
                return [];
            }
            $this->imageSizeInfo = $size;
        }
        return $this->imageSizeInfo;
    }

    public function width(): int
    {
        $imageSize = $this->getImageSize();
        return (int) $imageSize[0];
    }

    public function height(): int
    {
        $imageSize = $this->getImageSize();
        return (int) $imageSize[1];
    }

    public function type(): int
    {
        $imageSize = $this->getImageSize();
        return (int) $imageSize[2];
    }

    public function attr(): string
    {
        $imageSize = $this->getImageSize();
        return (string) $imageSize[3];
    }

    public function bits(): int
    {
        $imageSize = $this->getImageSize();
        return (int) $imageSize['bits'];
    }

    public function channels(): int
    {
        $imageSize = $this->getImageSize();
        return (int) $imageSize['channels'];
    }

    public function mime(): string
    {
        $imageSize = $this->getImageSize();
        return (string) $imageSize['mime'];
    }
}
