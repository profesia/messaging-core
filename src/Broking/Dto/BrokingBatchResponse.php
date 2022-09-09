<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Dto;

class BrokingBatchResponse
{
    /** @var MessageStatus[] */
    private array $messageStatuses;

    private function __construct(array $messageStatuses)
    {
        $this->messageStatuses = $messageStatuses;
    }

    public static function createFromMessageStatuses(MessageStatus... $messageStatuses): self
    {
        return new self(
            $messageStatuses
        );
    }

    public static function createForKeys(array $keys, bool $isSuccessful, ?string $reason = null): self
    {
        $statuses = [];
        foreach ($keys as $key) {
            $statuses[$key] = new MessageStatus($isSuccessful, $reason);
        }

        return new self(
            $statuses
        );
    }

    /**
     * @return MessageStatus[]
     */
    public function getMessageStatuses(): array
    {
        return $this->messageStatuses;
    }
}
