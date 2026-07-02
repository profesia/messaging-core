<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Dto\Sending;

use DateTimeImmutable;
use JsonException;
use Profesia\MessagingCore\Broking\Exception\MessagePayloadEncodingException;

final class PubSubMessage extends AbstractMessage
{
    public const EVENT_DATA       = 'data';
    public const EVENT_ATTRIBUTES = 'attributes';

    public function __construct(
        string $resource,
        string $eventType,
        string $provider,
        string $objectId,
        DateTimeImmutable $eventOccurredOn,
        string $correlationId,
        string $subscribeName,
        string $topic,
        array $payload,
    ) {
        parent::__construct(
            $topic,
            $provider,
            $eventType,
            $eventOccurredOn,
            $correlationId,
            $payload,
            $resource,
            $objectId,
            $subscribeName,
        );
    }

    public function toArray(): array
    {
        $attributes = [
            self::EVENT_RESOURCE       => $this->resource,
            self::EVENT_TYPE           => $this->eventType,
            self::EVENT_PROVIDER       => $this->provider,
            self::EVENT_OBJECT_ID      => $this->objectId,
            self::EVENT_OCCURRED_ON    => $this->eventOccurredOn->format('Y-m-d H:i:s.u'),
            self::EVENT_CORRELATION_ID => $this->correlationId,
            self::EVENT_SUBSCRIBE_NAME => $this->subscribeName,
        ];

        return [
            self::EVENT_ATTRIBUTES => $attributes,
            self::EVENT_DATA       => [...$attributes, self::MESSAGE_PAYLOAD => $this->payload],
        ];
    }

    public function encode(): array
    {
        $attributes = [
            self::EVENT_RESOURCE       => $this->resource,
            self::EVENT_TYPE           => $this->eventType,
            self::EVENT_PROVIDER       => $this->provider,
            self::EVENT_OBJECT_ID      => $this->objectId,
            self::EVENT_OCCURRED_ON    => $this->eventOccurredOn->format('Y-m-d H:i:s.u'),
            self::EVENT_CORRELATION_ID => $this->correlationId,
            self::EVENT_SUBSCRIBE_NAME => $this->subscribeName,
        ];

        try {
            return [
                self::EVENT_ATTRIBUTES => $attributes,
                self::EVENT_DATA       => json_encode(
                    [...$attributes, self::MESSAGE_PAYLOAD => $this->payload],
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
}
