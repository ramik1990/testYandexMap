<?php

namespace App\Services\YandexMaps\Exceptions;

class BlockedException extends ParserException
{
    public function errorCode(): string
    {
        return 'blocked';
    }
}
