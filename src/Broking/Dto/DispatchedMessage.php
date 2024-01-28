<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Dto;

final class DispatchedMessage
{
    private Message       $message;
    private BrokingStatus $status;

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
        return (array)json_decode($this->message->toArray()[Message::EVENT_DATA], true);
    }

    public function getEventAttributes(): array
    {
        return $this->message->toArray()[Message::EVENT_ATTRIBUTES];
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
