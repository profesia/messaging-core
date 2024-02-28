<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Dto\Sending;

use DateTimeImmutable;
use JsonException;
use Profesia\MessagingCore\Broking\Exception\MessagePayloadEncodingException;

final class Message
{
    public const EVENT_CORRELATION_ID = 'correlationId';
    public const EVENT_TYPE           = 'eventType';
    public const EVENT_OCCURRED_ON    = 'eventOccurredOn';
    public const EVENT_RESOURCE       = 'resource';
    public const EVENT_PROVIDER       = 'provider';
    public const EVENT_OBJECT_ID      = 'objectId';
    public const EVENT_SUBSCRIBE_NAME = 'subscribeName';
    public const EVENT_DATA           = 'data';
    public const EVENT_ATTRIBUTES     = 'attributes';
    public const MESSAGE_PAYLOAD      = 'payload';

    public function __construct(
        private readonly string $resource,
        private readonly string $eventType,
        private readonly string $provider,
        private readonly string $objectId,
        private readonly DateTimeImmutable $occurredOn,
        private readonly string $correlationId,
        private readonly string $subscribeName,
        private readonly string $topic,
        private readonly array $payload
    )
    {
    }

    public function toArray(): array
    {
        $attributes = [
            self::EVENT_RESOURCE       => $this->resource,
            self::EVENT_TYPE           => $this->eventType,
            self::EVENT_PROVIDER       => $this->provider,
            self::EVENT_OBJECT_ID      => $this->objectId,
            self::EVENT_OCCURRED_ON    => $this->occurredOn->format('Y-m-d H:i:s.u'),
            self::EVENT_CORRELATION_ID => $this->correlationId,
            self::EVENT_SUBSCRIBE_NAME => $this->subscribeName,
        ];

        return [
            self::EVENT_ATTRIBUTES => $attributes,
            self::EVENT_DATA       => array_merge($attributes, [self::MESSAGE_PAYLOAD => $this->payload])
        ];
    }

    public function encode(): array
    {
        $attributes = [
            self::EVENT_RESOURCE       => $this->resource,
            self::EVENT_TYPE           => $this->eventType,
            self::EVENT_PROVIDER       => $this->provider,
            self::EVENT_OBJECT_ID      => $this->objectId,
            self::EVENT_OCCURRED_ON    => $this->occurredOn->format('Y-m-d H:i:s.u'),
            self::EVENT_CORRELATION_ID => $this->correlationId,
            self::EVENT_SUBSCRIBE_NAME => $this->subscribeName,
        ];

        try {
            return [
                self::EVENT_ATTRIBUTES => $attributes,
                self::EVENT_DATA       => json_encode(
                    array_merge($attributes, [self::MESSAGE_PAYLOAD => $this->payload]),
                    JSON_THROW_ON_ERROR
                ),
            ];
        } catch (JsonException $e) {
            throw new MessagePayloadEncodingException(sprintf('Failed to encode message payload. Cause: [{%s}]', $e->getMessage()));
        }
    }

    public function getAttributes(): array
    {
        return $this->toArray()[self::EVENT_ATTRIBUTES];
    }

    public function getData(): array
    {
        return $this->toArray()[self::EVENT_DATA];
    }

    public function getTopic(): string
    {
        return $this->topic;
    }
}

