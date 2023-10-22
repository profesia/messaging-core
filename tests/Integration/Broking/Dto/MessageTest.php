<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Test\Integration\Broking\Dto;

use PHPUnit\Framework\TestCase;
use DateTimeImmutable;
use Profesia\MessagingCore\Broking\Dto\Message;

class MessageTest extends TestCase
{
    public function provideDataForDataGettingTest(): array
    {
        return [
            [
                [
                    'resource'      => 'resource1',
                    'eventType'     => 'eventType1',
                    'provider'      => 'provider1',
                    'objectId'      => 'objectId1',
                    'occurredOn'    => new DateTimeImmutable(),
                    'correlationId' => 'correlationId1',
                    'target'        => 'target1',
                    'subscribeName' => 'subscribeName1',
                    'payload'       => [
                        1
                    ],
                    'isPublic'      => true,
                ]
            ],
            [
                [
                    'resource'      => 'resource2',
                    'eventType'     => 'eventType2',
                    'provider'      => 'provider2',
                    'objectId'      => 'objectId2',
                    'occurredOn'    => new DateTimeImmutable(),
                    'correlationId' => 'correlationId2',
                    'target'        => 'target2',
                    'subscribeName' => 'subscribeName2',
                    'payload'       => [
                        2
                    ],
                    'isPublic'      => false,
                ]
            ]
        ];
    }

    /**
     * @param array $data
     * @return void
     *
     * @dataProvider provideDataForDataGettingTest
     */
    public function testCanGetData(array $data): void
    {
        $message = new Message(
            $data['resource'],
            $data['eventType'],
            $data['provider'],
            $data['objectId'],
            $data['occurredOn'],
            $data['correlationId'],
            $data['target'],
            $data['subscribeName'],
            $data['payload'],
            $data['isPublic']
        );

        $this->assertEquals($data['isPublic'], $message->isPublic());

        $attributes = [
            Message::EVENT_RESOURCE       => $data['resource'],
            Message::EVENT_TYPE           => $data['eventType'],
            Message::EVENT_PROVIDER       => $data['provider'],
            Message::EVENT_OBJECT_ID      => $data['objectId'],
            Message::EVENT_OCCURRED_ON    => $data['occurredOn']->format('Y-m-d H:i:s.u'),
            Message::EVENT_CORRELATION_ID => $data['correlationId'],
            Message::EVENT_TARGET         => $data['target'],
            Message::EVENT_SUBSCRIBE_NAME => $data['subscribeName'],
        ];

        $messageToCompare = [
            Message::EVENT_ATTRIBUTES => $attributes,
            Message::EVENT_DATA       => json_encode(
                array_merge($attributes, [Message::MESSAGE_PAYLOAD => $data['payload']])
            ),
        ];

        $this->assertEquals($messageToCompare, $message->toArray());
    }
}