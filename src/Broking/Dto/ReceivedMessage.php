<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Dto;

use JsonException;
use Profesia\MessagingCore\Broking\Exception\ReceivedMessageDecodingFailedException;
use Profesia\MessagingCore\Broking\Exception\ReceivedMessagePayloadDecodingFailedException;

class ReceivedMessage
{
    private array $payload;

    private function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public static function createFromJsonString(string $json): self
    {
        try {
            return new self(
                (array)json_decode(
                    $json,
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                )
            );
        } catch (JsonException $e) {
            throw new ReceivedMessageDecodingFailedException("Failed to decode received message. Cause: [{$e->getMessage()}]");
        }
    }

    public function getEventType(): string
    {
        return $this->payload['message'][Message::EVENT_ATTRIBUTES][Message::EVENT_TYPE];
    }

    public function decodePayload(): array
    {
        try {
            return
                (array)json_decode(
                    base64_decode(
                        $this->payload['message']
                    ),
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );
        } catch (JsonException $e) {
            throw new ReceivedMessagePayloadDecodingFailedException("Failed to decode received message payload. Cause: [{$e->getMessage()}]");
        }
    }
}