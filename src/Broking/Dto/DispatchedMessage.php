<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Dto;

final class DispatchedMessage
{
    public function __construct(
        private Message $message,
        private BrokingStatus $status
    ) {
    }

    public function getMessage(): Message
    {
        return $this->message;
    }

    public function wasDispatchedSuccessfully(): bool
    {
        return $this->status->isSuccessful();
    }

    public function getDispatchReason(): ?string
    {
        return $this->status->getReason();
    }
}
