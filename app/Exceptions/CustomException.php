<?php

namespace App\Exceptions;

use Exception;

class CustomException extends Exception
{
    protected string $generalError;
    protected ?string $sysError;
    protected int $statusCode;

    public function __construct(
        string $generalError = 'System error occurred. Please contact system administrator.',
        ?string $sysError = null,
        int $statusCode = 500,
        ?\Throwable $previous = null
    ) {
        $this->generalError = $generalError;
        $this->sysError = $sysError;
        $this->statusCode = $statusCode;

        // 使用 sysError 作為 Exception 的 message（如果有的話）
        parent::__construct($sysError ?? $generalError, $statusCode, $previous);
    }

    public function getGeneralError(): string
    {
        return $this->generalError;
    }

    public function getSysError(): ?string
    {
        return $this->sysError;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
