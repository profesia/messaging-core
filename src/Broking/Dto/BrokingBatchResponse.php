<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Dto;

final class BrokingBatchResponse
{
    /** @var DispatchedMessage[] */
    private array $dispatchedMessages;


    private function __construct(DispatchedMessage...$dispatchedMessages)
    {
        $this->dispatchedMessages = $dispatchedMessages;
    }

    public static function createForMessagesWithBatchStatus(bool $isSuccessful, ?string $reason = null, Message...$messages): self
    {
        $dispatchedMessages = [];
        foreach ($messages as $key => $message) {
            $dispatchedMessages[$key] = new DispatchedMessage(
                $message,
                new BrokingStatus($isSuccessful, $reason)
            );
        }

        return new self(
            ...$dispatchedMessages
        );
    }

    public static function createForMessagesWithIndividualStatus(DispatchedMessage...$dispatchedMessages): self
    {
        return new self(
            ...$dispatchedMessages
        );
    }

    /**
     * @return DispatchedMessage[]
     */
    public function getDispatchedMessages(): array
    {
        return $this->dispatchedMessages;
    }
}
