<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Dto\Sending\Factory;

use DateTimeImmutable;
use Profesia\MessagingCore\Broking\Dto\Sending\PubSubMessage;
use Profesia\MessagingCoreContracts\Broking\Dto\Sending\Factory\MessageFactoryInterface;

final class PubSubMessageFactory implements MessageFactoryInterface
{
    public function create(
        string $resource,
        string $eventType,
        string $provider,
        string $objectId,
        DateTimeImmutable $eventOccurredOn,
        string $correlationId,
        string $subscribeName,
        string $topic,
        array $payload,
    ): PubSubMessage {
        return new PubSubMessage(
            $resource,
            $eventType,
            $provider,
            $objectId,
            $eventOccurredOn,
            $correlationId,
            $subscribeName,
            $topic,
            $payload,
        );
    }
}
