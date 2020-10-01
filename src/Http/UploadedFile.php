<?php

namespace Horizom\Http;

use GuzzleHttp\Psr7\UploadedFile as BaseUploadedFile;
use Psr\Http\Message\UploadedFileInterface;

class UploadedFile extends BaseUploadedFile implements UploadedFileInterface
{
}
