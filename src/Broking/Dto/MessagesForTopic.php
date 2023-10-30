<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Dto;

final class MessagesForTopic
{
    /** @var Message[] */
    private array  $messages;
    private string $topic;

    /**
     * @param string $topic
     * @param Message[] $messages
     */
    public function __construct(string $topic, array $messages)
    {
        $this->topic    = $topic;
        $this->messages = $messages;
    }


    public static function createFromTopicAndMessages(string $topic, Message...$messages): MessagesForTopic
    {
        return new self(
            $topic,
            ...$messages
        );
    }

    public function getMessagesData(): array
    {
        $data = [];
        foreach ($this->messages as $key => $message) {
            $data[$key] = $message->toArray();
        }

        return $data;
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function getTopic(): string
    {
        return $this->topic;
    }
}