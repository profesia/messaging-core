<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Dto\Sending;

final class MessagesForTopic
{
    /**
     * @param string             $topic
     * @param MessageInterface[] $messages
     */
    public function __construct(
        private readonly string $topic,
        private readonly array $messages
    ) {
    }


    public static function createFromTopicAndMessages(string $topic, MessageInterface ...$messages): MessagesForTopic
    {
        return new self(
            $topic,
            $messages
        );
    }

    /**
     * @return MessageInterface[]
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