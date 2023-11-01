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
    private static function createMessages(int $number, array $forcedValues = []): array
    {
        $messages = [];
        for ($i = 1; $i <= $number; $i++) {
            $messages[] = new Message(
                $forcedValues['resource'] ?? "resource{$i}",
                $forcedValues['eventType'] ?? "eventType{$i}",
                $forcedValues['provider'] ?? "provider{$i}",
                $forcedValues['objectId'] ?? "objectId{$i}",
                new DateTimeImmutable(),
                $forcedValues['correlationId'] ?? 'correlationId',
                $forcedValues['subscribeName'] ?? "subscribeName{$i}",
                $forcedValues['topic'] ?? "topic{$i}",
                $forcedValues['data'] ?? [
                'data' => $i,
            ]
            );
        }

        return $messages;
    }
}
