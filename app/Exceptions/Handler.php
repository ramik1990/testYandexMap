<?php

namespace App\Exceptions;

use App\Services\YandexMaps\Exceptions\InvalidUrlException;
use App\Services\YandexMaps\Exceptions\ParserException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->renderable(fn (InvalidUrlException $e) => new JsonResponse([
            'message' => $e->getMessage(),
            'code' => $e->errorCode(),
            'errors' => ['url' => [$e->getMessage()]],
        ], 422));

        $this->renderable(fn (ParserException $e) => new JsonResponse([
            'message' => $e->getMessage(),
            'code' => $e->errorCode(),
        ], 503));
    }
}
