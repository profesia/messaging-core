<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Dto;

use DateTimeImmutable;

final class Message
{
    public const EVENT_CORRELATION_ID = 'correlationId';
    public const EVENT_TYPE           = 'eventType';
    public const EVENT_OCCURRED_ON    = 'eventOccurredOn';
    public const EVENT_RESOURCE       = 'resource';
    public const EVENT_PROVIDER       = 'provider';
    public const EVENT_OBJECT_ID      = 'objectId';
    public const EVENT_TARGET         = 'target';
    public const EVENT_ATTRIBUTES     = 'attributes';
    public const MESSAGE_PAYLOAD      = 'payload';
    public const EVENT_DATA           = 'data';

    public function __construct(
        private string $resource,
        private string $eventType,
        private string $provider,
        private string $objectId,
        private DateTimeImmutable $occurredOn,
        private string $correlationId,
        private string $target,
        private array $payload
    ) {
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
            self::EVENT_TARGET         => $this->target,
        ];

        return [
            self::EVENT_ATTRIBUTES => $attributes,
            self::EVENT_DATA       => json_encode(
                array_merge($attributes, [self::MESSAGE_PAYLOAD => $this->payload])
            ),
        ];
    }
}

