<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Dto\Sending;

use DateTimeImmutable;
use JsonException;
use Profesia\MessagingCore\Broking\Exception\MessagePayloadEncodingException;

class AWSEventBridgeMessage implements MessageInterface
{
    //aws attributes
    public const DETAIL_TYPE = 'DetailType';
    public const TIME        = 'Time';
    public const SOURCE      = 'Source';
    public const DETAIL      = 'Detail';

    //message attributes
    public const EVENT_PROVIDER       = 'provider';
    public const EVENT_TYPE           = 'eventType';
    public const EVENT_CORRELATION_ID = 'correlationId';
    public const EVENT_OCCURRED_ON    = 'eventOccurredOn';
    public const MESSAGE_PAYLOAD      = 'payload';

    private string $topic; //topic = eventBusName
    private string $provider;
    private string $eventType;
    private DateTimeImmutable $eventOccurredOn;
    private string $correlationId;
    private array $payload;

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

    public function encode(): array
    {
        try {
            return array_merge(
                $this->getAwsAttributes(),
                [
                    self::DETAIL => json_encode(
                        array_merge($this->getMessageAttributes(), [self::MESSAGE_PAYLOAD => $this->payload]),
                        JSON_THROW_ON_ERROR
                    ),
                ]
            );
        } catch (JsonException $e) {
            throw new MessagePayloadEncodingException(sprintf('Failed to encode message payload. Cause: [{%s}]', $e->getMessage()));
        }
    }

    public function toArray(): array
    {
        return array_merge(
            $this->getAwsAttributes(),
            [self::DETAIL => array_merge($this->getMessageAttributes(), [self::MESSAGE_PAYLOAD => $this->payload])]
        );
    }

    public function getTopic(): string
    {
        return $this->topic;
    }

    public function getAttributes(): array
    {
        return $this->getMessageAttributes();
    }

    public function getData(): array
    {
        return $this->toArray()[self::DETAIL];
    }

    private function getAwsAttributes(): array
    {
        return [
            self::SOURCE      => $this->provider,
            self::DETAIL_TYPE => $this->eventType,
            self::TIME        => $this->eventOccurredOn->format('Y-m-d H:i:s.u'),
        ];
    }

    private function getMessageAttributes(): array
    {
        return [
            self::EVENT_PROVIDER       => $this->provider,
            self::EVENT_TYPE           => $this->eventType,
            self::EVENT_CORRELATION_ID => $this->correlationId,
            self::EVENT_OCCURRED_ON    => $this->eventOccurredOn->format('Y-m-d H:i:s.u'),
        ];
    }
}
