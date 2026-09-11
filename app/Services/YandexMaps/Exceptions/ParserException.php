<?php

namespace App\Services\YandexMaps\Exceptions;

use RuntimeException;
use Throwable;

abstract class ParserException extends RuntimeException
{
    public function __construct(string $message, private readonly array $context = [], ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    abstract public function errorCode(): string;

    public function isRetryable(): bool
    {
        return true;
    }

    public function context(): array
    {
        return $this->context;
    }
}
