<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Dto;

use DateTimeImmutable;

final class Message implements MessageInterface
{
    public const EVENT_CORRELATION_ID = 'correlationId';
    public const EVENT_TYPE           = 'eventType';
    public const EVENT_OCCURRED_ON    = 'eventOccurredOn';
    public const EVENT_RESOURCE       = 'resource';
    public const EVENT_PROVIDER       = 'provider';
    public const EVENT_OBJECT_ID      = 'objectId';
    public const EVENT_ATTRIBUTES     = 'attributes';
    public const MESSAGE_PAYLOAD      = 'payload';
    public const EVENT_DATA           = 'data';
    public const EVENT_SUBSCRIBE_NAME = 'subscribeName';

    private string            $resource;
    private string            $eventType;
    private string            $provider;
    private string            $objectId;
    private DateTimeImmutable $occurredOn;
    private string            $correlationId;
    private string            $subscribeName;
    private string            $topic;
    private array             $payload;

    public function __construct(
        string $resource,
        string $eventType,
        string $provider,
        string $objectId,
        DateTimeImmutable $occurredOn,
        string $correlationId,
        string $subscribeName,
        string $topic,
        array $payload
    )
    {
        $this->resource      = $resource;
        $this->eventType     = $eventType;
        $this->provider      = $provider;
        $this->objectId      = $objectId;
        $this->occurredOn    = $occurredOn;
        $this->correlationId = $correlationId;
        $this->subscribeName = $subscribeName;
        $this->topic         = $topic;
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
            self::EVENT_SUBSCRIBE_NAME => $this->subscribeName,
        ];

        return [
            self::EVENT_ATTRIBUTES => $attributes,
            self::EVENT_DATA       => json_encode(
                array_merge($attributes, [self::MESSAGE_PAYLOAD => $this->payload])
            ),
        ];
    }

    public function getTopic(): string
    {
        return $this->topic;
    }
}

