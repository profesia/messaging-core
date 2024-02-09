<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Test\Assets;

use DateTimeImmutable;
use Profesia\MessagingCore\Broking\Dto\Message;

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
}
