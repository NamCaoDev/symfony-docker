<?php

namespace App\Exception;

class ValidationException extends \RuntimeException
{
    private array $errors;

    public function __construct(string $message = 'Validation failed.', array $errors = [], int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
