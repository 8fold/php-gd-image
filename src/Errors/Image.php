<?php
declare(strict_types=1);

namespace Eightfold\Image\Errors;

enum Image
{
    case FailedToCopyFromUrl;
    case FileNotFound;
    case UnsupportedMimeType;
    case FailedToScaleImage;
    case FailedToSaveAfterScaling;
}
