<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Dto\Sending\Factory;

use DateTimeImmutable;
use Profesia\MessagingCore\Broking\Dto\Sending\AwsMessage;
use Profesia\MessagingCoreContracts\Broking\Dto\Sending\Factory\MessageFactoryInterface;

final class AwsMessageFactory implements MessageFactoryInterface
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
    ): AwsMessage {
        return new AwsMessage(
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
