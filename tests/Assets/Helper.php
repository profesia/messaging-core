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
    private static function createMessages(int $number): array
    {
        $messages = [];
        for ($i = 1; $i <= $number; $i++) {
            $messages[] = new Message(
                "resource{$i}",
                "eventType{$i}",
                "provider{$i}",
                "objectId{$i}",
                new DateTimeImmutable(),
                'correlationId',
                "target{$i}",
                [
                    'data' => $i,
                ]
            );
        }

        return $messages;
    }
}
