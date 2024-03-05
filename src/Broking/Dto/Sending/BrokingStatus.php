<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Dto\Sending;

final class BrokingStatus
{
    public function __construct(
        private readonly bool $isSuccessful,
        private readonly ?string $reason = null
    )
    {
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
