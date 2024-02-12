<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Dto\Sending;

final class BrokingBatchResponse
{
    /** @var DispatchedMessage[] */
    private array $dispatchedMessages;

    private function __construct(DispatchedMessage...$dispatchedMessages)
    {
        $this->dispatchedMessages = $dispatchedMessages;
    }

    public static function createEmpty(): self
    {
        return new self();
    }

    public static function createForMessagesWithBatchStatus(bool $isSuccessful, ?string $reason = null, Message...$messages): self
    {
        $dispatchedMessages = [];
        $index              = 0;
        foreach ($messages as $message) {
            $dispatchedMessages[$index++] = new DispatchedMessage(
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

    public function appendDispatchedMessages(DispatchedMessage...$dispatchedMessages): self
    {
        return new self(
            ...array_merge($this->dispatchedMessages, $dispatchedMessages)
        );
    }
}
