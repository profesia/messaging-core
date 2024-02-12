<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Dto\Sending;

final class DispatchedMessage
{
    private Message $message;
    private BrokingStatus    $status;

    public function __construct(
        Message $message,
        BrokingStatus $status
    )
    {
        $this->message = $message;
        $this->status  = $status;
    }

    public function getTopic(): string
    {
        return $this->message->getTopic();
    }

    public function getEventData(): array
    {
        return $this->message->getData();
    }

    public function getEventAttributes(): array
    {
        return $this->message->getAttributes();
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
