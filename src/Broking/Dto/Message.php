<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Dto;

use DateTimeImmutable;

final class Message
{
    private const EVENT_CORRELATION_ID = 'correlationId';
    private const EVENT_TYPE           = 'eventType';
    private const EVENT_OCCURRED_ON    = 'eventOccurredOn';
    private const EVENT_RESOURCE       = 'resource';
    private const EVENT_PROVIDER       = 'provider';
    private const EVENT_OBJECT_ID      = 'objectId';
    private const EVENT_TARGET         = 'target';
    private const EVENT_ATTRIBUTES     = 'attributes';
    private const MESSAGE_PAYLOAD      = 'payload';

    public const EVENT_DATA = 'data';

    private string $resource;
    private string $eventType;
    private string $provider;
    private string $objectId;
    private DateTimeImmutable $occurredOn;
    private string $correlationId;
    private string $target;
    private array $payload;

    public function __construct(
        string $resource,
        string $eventType,
        string $provider,
        string $objectId,
        DateTimeImmutable $occurredOn,
        string $correlationId,
        string $target,
        array $payload
    ) {
        $this->resource      = $resource;
        $this->eventType     = $eventType;
        $this->provider      = $provider;
        $this->objectId      = $objectId;
        $this->occurredOn    = $occurredOn;
        $this->correlationId = $correlationId;
        $this->target        = $target;
        $this->payload       = $payload;
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

        $data = array_merge($attributes, [self::MESSAGE_PAYLOAD => $this->payload]);

        return [
            self::EVENT_ATTRIBUTES => $attributes,
            self::EVENT_DATA       => json_encode($data),
        ];
    }
}

