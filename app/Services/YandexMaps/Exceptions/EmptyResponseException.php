<?php

namespace App\Services\YandexMaps\Exceptions;

class EmptyResponseException extends ParserException
{
    public function errorCode(): string
    {
        return 'empty_response';
    }
}
