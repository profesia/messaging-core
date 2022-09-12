<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Dto;

class BrokingStatus
{
    private bool $isSuccessful;
    private ?string $reason;

    public function __construct(bool $isSuccessful, ?string $reason = null)
    {
        $this->isSuccessful = $isSuccessful;
        $this->reason       = $reason;
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
