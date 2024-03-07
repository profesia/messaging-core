<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Dto\Sending;

use DateTimeImmutable;

abstract class AbstractMessage implements MessageInterface
{
    //message attributes
    public const EVENT_RESOURCE       = 'resource';
    public const EVENT_OBJECT_ID      = 'objectId';
    public const EVENT_SUBSCRIBE_NAME = 'subscribeName';
    public const EVENT_PROVIDER       = 'provider';
    public const EVENT_TYPE           = 'eventType';
    public const EVENT_CORRELATION_ID = 'correlationId';
    public const EVENT_OCCURRED_ON    = 'eventOccurredOn';
    public const MESSAGE_PAYLOAD      = 'payload';

    public function __construct(
        protected readonly string $topic,
        protected readonly string $provider,
        protected readonly string $eventType,
        protected readonly DateTimeImmutable $eventOccurredOn,
        protected readonly string $correlationId,
        protected readonly array $payload,
        protected readonly string $resource,
        protected readonly string $objectId,
        protected readonly string $subscribeName,
    ) {
    }

    public function getTopic(): string
    {
        return $this->topic;
    }

    abstract public function getAttributes(): array;

    abstract public function getData(): array;
}
