<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Dto\Sending;

final class MessagesForTopic
{
    /**
     * @param string $topic
     * @param Message[] $messages
     */
    public function __construct(
        private readonly string $topic,
        private readonly array $messages
    )
    {
    }


    public static function createFromTopicAndMessages(string $topic, Message ...$messages): MessagesForTopic
    {
        return new self(
            $topic,
            $messages
        );
    }

    /**
     * @return Message[]
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    public function getTopic(): string
    {
        return $this->topic;
    }
}