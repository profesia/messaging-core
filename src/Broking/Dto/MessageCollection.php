<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Dto;

final class MessageCollection
{
    private function __construct(
        private string $channel,
        private array $messages
    ) {
    }

    public static function createFromMessagesAndChannel(string $channel, Message...$messages): MessageCollection
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
