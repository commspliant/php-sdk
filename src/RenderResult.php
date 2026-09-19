<?php

declare(strict_types=1);

namespace CommsPliant;

final class RenderResult
{
    public function __construct(
        public readonly string $body,
        public readonly string $contentType,
        public readonly string $requestId,
        public readonly ?string $contentDisposition = null,
    ) {
    }
}
