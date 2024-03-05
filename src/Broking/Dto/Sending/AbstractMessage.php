<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Dto\Sending;

use DateTimeImmutable;

abstract class AbstractMessage implements MessageInterface
{
    //message attributes
    public const EVENT_PROVIDER       = 'provider';
    public const EVENT_TYPE           = 'eventType';
    public const EVENT_CORRELATION_ID = 'correlationId';
    public const EVENT_OCCURRED_ON    = 'eventOccurredOn';
    public const MESSAGE_PAYLOAD      = 'payload';

    protected string $topic;
    protected string $provider;
    protected string $eventType;
    protected DateTimeImmutable $eventOccurredOn;
    protected string $correlationId;
    protected array $payload;

    public function __construct(
        string $topic,
        string $provider,
        string $eventType,
        DateTimeImmutable $eventOccurredOn,
        string $correlationId,
        array $payload
    ) {
        $this->topic           = $topic;
        $this->provider        = $provider;
        $this->eventType       = $eventType;
        $this->eventOccurredOn = $eventOccurredOn;
        $this->correlationId   = $correlationId;
        $this->payload         = $payload;
    }

    abstract public function encode(): array;

    abstract public function toArray(): array;

    public function getTopic(): string
    {
        return $this->topic;
    }

    abstract public function getAttributes(): array;

    abstract public function getData(): array;
}
