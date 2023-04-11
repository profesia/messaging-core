<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Dto;

use JsonException;
use Profesia\MessagingCore\Broking\Exception\ReceivedMessageDecodingFailedException;
use Profesia\MessagingCore\Broking\Exception\ReceivedMessagePayloadDecodingFailedException;

class ReceivedMessage
{
    private array $message;

    private function __construct(array $message)
    {
        $this->message = $message;
    }

    public static function createFromJsonString(string $json): self
    {
        try {
            $envelope =
                (array)json_decode(
                    $json,
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );

            $envelope['message'][Message::EVENT_DATA] = (array)json_decode(
                base64_decode(
                    $envelope['message'][Message::EVENT_DATA]
                ),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            return new self($envelope);
        } catch (JsonException $e) {
            throw new ReceivedMessageDecodingFailedException("Failed to decode received message. Cause: [{$e->getMessage()}]");
        }
    }

    public function getEventType(): string
    {
        return $this->message['message'][Message::EVENT_ATTRIBUTES][Message::EVENT_TYPE];
    }

    public function getDecodedMessage(): array
    {
        return $this->message['message'];
    }
}