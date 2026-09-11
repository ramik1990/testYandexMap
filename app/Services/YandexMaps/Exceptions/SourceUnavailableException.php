<?php

namespace App\Services\YandexMaps\Exceptions;

class SourceUnavailableException extends ParserException
{
    public function errorCode(): string
    {
        return 'source_unavailable';
    }
}
