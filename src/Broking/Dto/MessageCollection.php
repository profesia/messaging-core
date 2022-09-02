<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Dto;

final class MessageCollection
{
    /** @var Message[] */
    private array $messages;
    private string $channel;

    private function __construct(string $channel, Message...$messages)
    {
        $this->messages = $messages;
        $this->channel  = $channel;
    }

    public static function createFromMessagesAndChannel(array $messages, string $channel): MessageCollection
    {
        return new self(
               $channel,
            ...$messages
        );
    }

    public function getMessagesData(): array
    {
        $data = [];
        foreach ($this->messages as $message) {
            $data[] = $message->toArray();
        }

        return $data;
    }

    public function getChannel(): string
    {
        return $this->channel;
    }


}
