<?php

declare(strict_types=1);

namespace CommsPliant;

final class APIException extends \RuntimeException
{
    public function __construct(
        public readonly int $statusCode,
        string $message,
        public readonly ?string $errorCode = null,
        public readonly ?array $details = null,
        public readonly ?string $requestId = null,
    ) {
        parent::__construct($message, $statusCode);
    }
}
