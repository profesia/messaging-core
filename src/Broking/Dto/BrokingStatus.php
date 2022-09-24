<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Dto;

final class BrokingStatus
{
    public function __construct(
        private bool $isSuccessful,
        private ?string $reason = null
    ) {
    }

    public function isSuccessful(): bool
    {
        return $this->isSuccessful;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }
}
