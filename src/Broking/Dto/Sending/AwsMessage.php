<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Dto\Sending;

use DateTimeImmutable;
use JsonException;
use Profesia\MessagingCore\Broking\Exception\MessagePayloadEncodingException;

class AwsMessage extends AbstractMessage
{
    //aws attributes
    public const DETAIL_TYPE = 'DetailType';
    public const TIME        = 'Time';
    public const SOURCE      = 'Source';
    public const DETAIL      = 'Detail';

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

    public function encode(): array
    {
        try {
            return [
                ...$this->getAwsAttributes(),
                self::DETAIL => json_encode(
                    [...$this->getMessageAttributes(), self::MESSAGE_PAYLOAD => $this->payload],
                    JSON_THROW_ON_ERROR
                ),
            ];
        } catch (JsonException $e) {
            throw new MessagePayloadEncodingException(sprintf('Failed to encode message payload. Cause: [{%s}]', $e->getMessage()));
        }
    }

    public function toArray(): array
    {
        return [
            ...$this->getAwsAttributes(),
            self::DETAIL => [...$this->getMessageAttributes(), self::MESSAGE_PAYLOAD => $this->payload],
        ];
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
            self::EVENT_RESOURCE       => $this->resource,
            self::EVENT_OBJECT_ID      => $this->objectId,
            self::EVENT_SUBSCRIBE_NAME => $this->subscribeName,
        ];
    }
}
