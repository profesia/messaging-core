<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Test\Assets;

use DateTimeImmutable;
use Profesia\MessagingCore\Broking\Dto\Sending\AwsMessage;
use Profesia\MessagingCore\Broking\Dto\Sending\Message;

trait Helper
{
    /**
     * @param int $number
     *
     * @return Message[]
     */
    private static function createMessages(int $number, array $forcedValues = [], int $startIndex = 1): array
    {
        $messages = [];
        $index    = $startIndex;
        for ($i = 1; $i <= $number; $i++) {
            $messages[] = new Message(
                $forcedValues['resource'] ?? "resource{$index}",
                $forcedValues['eventType'] ?? "eventType{$index}",
                $forcedValues['provider'] ?? "provider{$index}",
                $forcedValues['objectId'] ?? "objectId{$index}",
                new DateTimeImmutable(),
                $forcedValues['correlationId'] ?? 'correlationId',
                $forcedValues['subscribeName'] ?? "subscribeName{$index}",
                $forcedValues['topic'] ?? "topic{$index}",
                $forcedValues['data'] ?? [
                'data' => $index,
            ]
            );
            $index++;
        }

        return $messages;
    }

    /**
     * @param int $number
     *
     * @return AwsMessage[]
     */
    private static function createAwsMessages(int $number, array $forcedValues = [], int $startIndex = 1): array
    {
        $messages = [];
        $index    = $startIndex;
        for ($i = 1; $i <= $number; $i++) {
            $messages[] = new AwsMessage(
                $forcedValues['topic'] ?? "topic{$index}",
                $forcedValues['provider'] ?? "provider{$index}",
                $forcedValues['eventType'] ?? "eventType{$index}",
                new DateTimeImmutable(),
                $forcedValues['correlationId'] ?? 'correlationId',
                $forcedValues['data'] ?? ['data' => $index],
                $forcedValues['resource'] ?? "resource{$index}",
                $forcedValues['objectId'] ?? "objectId{$index}",
                $forcedValues['subscribeName'] ?? "subscribeName{$index}",
            );
            $index++;
        }

        return $messages;
    }
}
