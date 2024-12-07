<?php

namespace App\Exception;

class SEOValidationException extends \InvalidArgumentException
{
    private array $errors;

    public function __construct(array $errors, string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message ?: implode(', ', $errors), $code, $previous);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
