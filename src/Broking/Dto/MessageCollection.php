<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Dto;

use Profesia\MessagingCore\Broking\Exception\KeyDoesNotExistException;

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
        foreach ($this->messages as $key => $message) {
            $data[$key] = $message->toArray();
        }

        return $data;
    }

    /**
     * @return int[]
     */
    public function getKeys(): array
    {
        return array_keys($this->messages);
    }

    public function getMessageData(int $index): array
    {
        if (array_key_exists($index, $this->messages) === false) {
            throw new KeyDoesNotExistException("Kes: [{$index}] does not exist in collection");
        }

        return $this->messages[$index]->toArray();
    }

    public function getChannel(): string
    {
        return $this->channel;
    }


}
