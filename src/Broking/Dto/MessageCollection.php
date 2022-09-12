<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Dto;

use Profesia\MessagingCore\Broking\Exception\KeyDoesNotExistException;

final class MessageCollection
{
    /** @var Message[] */
    private array $messages;
    private string $channel;

    private function __construct(string $channel, array $messages)
    {
        $this->messages = $messages;
        $this->channel  = $channel;
    }

    public static function createFromMessagesAndChannel(string $channel, Message... $messages): MessageCollection
    {
        return new self(
            $channel,
            $messages
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

    /**
     * @return Message[]
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    public function getChannel(): string
    {
        return $this->channel;
    }


}
