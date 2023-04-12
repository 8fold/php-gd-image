<?php
declare(strict_types=1);

namespace Eightfold\Image\Errors;

enum Environment: string
{
    case AllowUrlFOpenNotEnabled = 'Tried to copy file from URL without allow_url_fopen in php_ini';
    case CopyDestinationMustBeLocal = 'Tried to copy file to URL';
    case FailedToCreateDestinationDirectory = 'Tried to copy file to directory that was not found';
    case FailedToParseUrl = 'Tried to copy file from URL and could not parse url.'
}
