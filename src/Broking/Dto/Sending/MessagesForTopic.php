<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Dto\Sending;

final class MessagesForTopic
{
    /** @var MessageInterface[] */
    private array $messages;
    private string $topic;

    /**
     * @param string             $topic
     * @param MessageInterface[] $messages
     */
    public function __construct(string $topic, array $messages)
    {
        $this->topic    = $topic;
        $this->messages = $messages;
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