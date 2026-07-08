<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Dto\Receiving;

use JsonException;
use Profesia\MessagingCoreContracts\Broking\Dto\Receiving\ReceivedMessageInterface;
use Profesia\MessagingCore\Broking\Dto\Sending\PubSubMessage;
use Profesia\MessagingCore\Broking\Exception\ReceivedMessageBadStructureException;
use Profesia\MessagingCore\Broking\Exception\ReceivedMessageDecodingFailedException;
use Profesia\MessagingCoreContracts\Broking\Dto\Sending\AbstractMessage;

final class PubSubReceivedMessage implements ReceivedMessageInterface
{
    public const MESSAGE_KEY = 'message';

    /**
     * @param array{message: array{attributes: array, data: array}} $message
     */
    private function __construct(
        private readonly array $message
    )
    {
    }

    /**
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $data
     */
    public static function createFromRaw(array $attributes, array $data): self
    {
        $eventTypeKey = AbstractMessage::EVENT_TYPE;
        if (array_key_exists($eventTypeKey, $attributes) === false) {
            throw new ReceivedMessageBadStructureException(sprintf('Missing offset: [%s] in attributes', $eventTypeKey));
        }

        return new self([
            self::MESSAGE_KEY => [
                PubSubMessage::EVENT_ATTRIBUTES => $attributes,
                PubSubMessage::EVENT_DATA       => $data,
            ],
        ]);
    }

    public static function createFromJsonString(string $json): self
    {
        $messageKey    = self::MESSAGE_KEY;
        $attributesKey = PubSubMessage::EVENT_ATTRIBUTES;
        $dataKey       = PubSubMessage::EVENT_DATA;

        try {
            $envelope = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ReceivedMessageDecodingFailedException("Failed to decode received message. Cause: [{$e->getMessage()}]");
        }

        if (is_array($envelope) === false || array_key_exists($messageKey, $envelope) === false) {
            throw new ReceivedMessageBadStructureException('Missing offset: [message] in path: []');
        }

        $messageContent = $envelope[$messageKey];
        if (is_array($messageContent) === false || array_key_exists($attributesKey, $messageContent) === false) {
            throw new ReceivedMessageBadStructureException("Missing offset: [$attributesKey] in path: [message]");
        }

        if (array_key_exists($dataKey, $messageContent) === false) {
            throw new ReceivedMessageBadStructureException("Missing offset: [$dataKey] in path: [message]");
        }

        $attributes = $messageContent[$attributesKey];
        $rawData    = $messageContent[$dataKey];
        if (is_array($attributes) === false || is_string($rawData) === false) {
            throw new ReceivedMessageBadStructureException('Malformed message structure in path: [message]');
        }

        try {
            $decodedData = json_decode(
                base64_decode($rawData),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $e) {
            throw new ReceivedMessageDecodingFailedException("Failed to decode received message. Cause: [{$e->getMessage()}]");
        }

        if (is_array($decodedData) === false) {
            throw new ReceivedMessageBadStructureException("Missing offset: [$dataKey] in path: [message]");
        }

        $eventTypeKey = AbstractMessage::EVENT_TYPE;
        if (array_key_exists($eventTypeKey, $attributes) === false) {
            throw new ReceivedMessageBadStructureException("Missing offset: [$eventTypeKey] in path: [message, $attributesKey]");
        }

        return new self([
            $messageKey => [
                $attributesKey => $attributes,
                $dataKey       => $decodedData,
            ],
        ]);
    }

    public function getEventType(): string
    {
        $eventType = $this->message[self::MESSAGE_KEY][PubSubMessage::EVENT_ATTRIBUTES][PubSubMessage::EVENT_TYPE] ?? null;
        if (is_string($eventType) === false) {
            throw new ReceivedMessageBadStructureException(sprintf('Missing or invalid offset: [%s] in attributes', PubSubMessage::EVENT_TYPE));
        }

        return $eventType;
    }

    public function getSubscribeName(): string
    {
        $subscribeName = $this->message[self::MESSAGE_KEY][PubSubMessage::EVENT_ATTRIBUTES][PubSubMessage::EVENT_SUBSCRIBE_NAME] ?? null;
        if (is_string($subscribeName) === false) {
            throw new ReceivedMessageBadStructureException(sprintf('Missing or invalid offset: [%s] in attributes', PubSubMessage::EVENT_SUBSCRIBE_NAME));
        }

        return $subscribeName;
    }

    public function getDecodedMessage(): array
    {
        return $this->message[self::MESSAGE_KEY];
    }
}
