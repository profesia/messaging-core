<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Dto\Receiving;

use JsonException;
use Profesia\MessagingCore\Broking\Dto\Sending\Message;
use Profesia\MessagingCore\Broking\Exception\ReceivedMessageBadStructureException;
use Profesia\MessagingCore\Broking\Exception\ReceivedMessageDecodingFailedException;

final class PubSubReceivedMessage implements ReceivedMessageInterface
{
    public const MESSAGE_KEY = 'message';

    private function __construct(
        private readonly array $message
    )
    {
    }

    public static function createFromRaw(array $attributes, array $data): self
    {
        $eventTypeKey = Message::EVENT_TYPE;
        if (array_key_exists($eventTypeKey, $attributes) === false) {
            throw new ReceivedMessageBadStructureException(sprintf('Missing offset: [%s] in attributes', $eventTypeKey));
        }

        return new self([
            self::MESSAGE_KEY => [
                Message::EVENT_ATTRIBUTES => $attributes,
                Message::EVENT_DATA       => $data,
            ],
        ]);
    }

    public static function createFromJsonString(string $json): self
    {
        $messageKey = self::MESSAGE_KEY;
        try {
            $envelope =
                (array)json_decode(
                    $json,
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );

            if (array_key_exists($messageKey, $envelope) === false) {
                throw new ReceivedMessageBadStructureException('Missing offset: [message] in path: []');
            }

            $attributesKey = Message::EVENT_ATTRIBUTES;
            if (array_key_exists($attributesKey, $envelope[$messageKey]) === false) {
                throw new ReceivedMessageBadStructureException("Missing offset: [$attributesKey] in path: [message]");
            }

            $dataKey = Message::EVENT_DATA;
            if (array_key_exists($dataKey, $envelope[$messageKey]) === false) {
                throw new ReceivedMessageBadStructureException("Missing offset: [$dataKey] in path: [message]");
            }

            $envelope[$messageKey][$dataKey] = (array)json_decode(
                base64_decode(
                    $envelope[$messageKey][$dataKey]
                ),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            $eventTypeKey = Message::EVENT_TYPE;
            if (array_key_exists($eventTypeKey, $envelope[$messageKey][$attributesKey]) === false) {
                throw new ReceivedMessageBadStructureException("Missing offset: [$eventTypeKey] in path: [message, $attributesKey]");
            }

            return new self($envelope);
        } catch (JsonException $e) {
            throw new ReceivedMessageDecodingFailedException("Failed to decode received message. Cause: [{$e->getMessage()}]");
        }
    }

    public function getEventType(): string
    {
        return $this->message[self::MESSAGE_KEY][Message::EVENT_ATTRIBUTES][Message::EVENT_TYPE];
    }

    public function getSubscribeName(): string
    {
        return $this->message[self::MESSAGE_KEY][Message::EVENT_ATTRIBUTES][Message::EVENT_SUBSCRIBE_NAME];
    }

    public function getDecodedMessage(): array
    {
        return $this->message[self::MESSAGE_KEY];
    }
}