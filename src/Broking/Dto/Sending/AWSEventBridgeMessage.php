<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Dto\Sending;

use DateTimeImmutable;
use JsonException;
use Profesia\MessagingCore\Broking\Exception\MessagePayloadEncodingException;

class AWSEventBridgeMessage implements MessageInterface
{
    public const EVENT_CORRELATION_ID = 'correlationId'; //required
    public const EVENT_OCCURRED_ON    = 'eventOccurredOn'; // required
    public const TRACE_HEADER         = 'TraceHeader'; //todo: check AWS docs
    public const RESOURCES            = 'Resources';
    public const DETAIL_TYPE          = 'DetailType';
    public const TIME                 = 'Time';
    public const SOURCE               = 'Source';
    public const DETAIL               = 'Detail';

    private string $topic; //topic = eventBusName
    private string $source;
    private DateTimeImmutable $time;
    private string $detailType;
    private array $detail;
    private string $correlationId;
    private DateTimeImmutable $eventOccurredOn;

    public function __construct(
        string $topic,
        string $source,
        string $detailType,
        DateTimeImmutable $time,
        array $detail,
        string $correlationId,
        DateTimeImmutable $eventOccurredOn
    ) {
        $this->topic           = $topic;
        $this->detail          = $detail;
        $this->detailType      = $detailType;
        $this->time            = $time;
        $this->source          = $source;
        $this->correlationId   = $correlationId;
        $this->eventOccurredOn = $eventOccurredOn;
    }

    public function encode(): array
    {
        try {
            return [
                self::SOURCE               => $this->source,
                self::DETAIL_TYPE          => $this->detailType,
                self::TIME                 => $this->time->format('Y-m-d H:i:s.u'),
                self::DETAIL               => json_encode($this->detail, JSON_THROW_ON_ERROR),
                self::EVENT_CORRELATION_ID => $this->correlationId,
                self::EVENT_OCCURRED_ON    => $this->eventOccurredOn->format('Y-m-d H:i:s.u'),
            ];
        } catch (JsonException $e) {
            throw new MessagePayloadEncodingException(sprintf('Failed to encode message payload. Cause: [{%s}]', $e->getMessage()));
        }
    }

    public function getTopic(): string
    {
        return $this->topic;
    }

    public function getAttributes(): array
    {
        $attributes = $this->toArray();
        unset($attributes[self::DETAIL]);

        return $attributes;
    }

    public function toArray(): array
    {
        return [
            self::SOURCE               => $this->source,
            self::DETAIL_TYPE          => $this->detailType,
            self::TIME                 => $this->time->format('Y-m-d H:i:s.u'),
            self::DETAIL               => $this->detail,
            self::EVENT_CORRELATION_ID => $this->correlationId,
            self::EVENT_OCCURRED_ON    => $this->eventOccurredOn->format('Y-m-d H:i:s.u'),
        ];
    }

    public function getData(): array
    {
        return $this->toArray()[self::DETAIL];
    }
}
