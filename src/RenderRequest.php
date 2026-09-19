<?php

declare(strict_types=1);

namespace CommsPliant;

final class RenderRequest
{
    public function __construct(
        public readonly string $templateId,
        public readonly array $variables,
        public readonly ?string $templateVersionId = null,
    ) {
    }
}
