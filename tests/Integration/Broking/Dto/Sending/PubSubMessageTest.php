<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Test\Integration\Broking\Dto\Sending;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Profesia\MessagingCore\Broking\Dto\Sending\PubSubMessage;
use Profesia\MessagingCore\Broking\Exception\MessagePayloadEncodingException;
use Profesia\MessagingCore\Exception\AbstractRuntimeException;

class PubSubMessageTest extends TestCase
{
    public function provideDataForDataGettingTest(): array
    {
        return [
            'event1'                  => [
                [
                    'resource'      => 'resource1',
                    'eventType'     => 'eventType1',
                    'provider'      => 'provider1',
                    'objectId'      => 'objectId1',
                    'occurredOn'    => new DateTimeImmutable(),
                    'correlationId' => 'correlationId1',
                    'subscribeName' => 'subscribeName1',
                    'topic'         => 'topicName1',
                    'payload'       => [
                        1,
                    ],
                ],
            ],
            'event2'                  => [
                [
                    'resource'      => 'resource2',
                    'eventType'     => 'eventType2',
                    'provider'      => 'provider2',
                    'objectId'      => 'objectId2',
                    'occurredOn'    => new DateTimeImmutable(),
                    'correlationId' => 'correlationId2',
                    'subscribeName' => 'subscribeName2',
                    'topic'         => 'topicName2',
                    'payload'       => [
                        2,
                    ],
                ],
            ],
            'event-with-encode-error' => [
                [
                    'resource'      => 'resource3',
                    'eventType'     => 'eventType3',
                    'provider'      => 'provider3',
                    'objectId'      => 'objectId3',
                    'occurredOn'    => new DateTimeImmutable(),
                    'correlationId' => 'correlationId3',
                    'subscribeName' => 'subscribeName3',
                    'topic'         => 'topicName3',
                    'payload'       => [
                        'test-field' => pack('S4', 1974, 106, 28225, 32725),
                    ],
                ],
                new MessagePayloadEncodingException('Failed to encode message payload. Cause: [{Malformed UTF-8 characters, possibly incorrectly encoded}]')
            ]
        ];
    }

    /**
     * @param array $data
     *
     * @return void
     *
     * @dataProvider provideDataForDataGettingTest
     */
    public function testCanGetData(array $data, ?AbstractRuntimeException $exception = null): void
    {
        $message = new PubSubMessage(
            $data['resource'],
            $data['eventType'],
            $data['provider'],
            $data['objectId'],
            $data['occurredOn'],
            $data['correlationId'],
            $data['subscribeName'],
            $data['topic'],
            $data['payload'],
        );

        $this->assertEquals($data['topic'], $message->getTopic());

        $attributes = [
            PubSubMessage::EVENT_RESOURCE       => $data['resource'],
            PubSubMessage::EVENT_TYPE           => $data['eventType'],
            PubSubMessage::EVENT_PROVIDER       => $data['provider'],
            PubSubMessage::EVENT_OBJECT_ID      => $data['objectId'],
            PubSubMessage::EVENT_OCCURRED_ON    => $data['occurredOn']->format('Y-m-d H:i:s.u'),
            PubSubMessage::EVENT_CORRELATION_ID => $data['correlationId'],
            PubSubMessage::EVENT_SUBSCRIBE_NAME => $data['subscribeName'],
        ];

        $messageToCompare = [
            PubSubMessage::EVENT_ATTRIBUTES => $attributes,
            PubSubMessage::EVENT_DATA       => array_merge($attributes, [PubSubMessage::MESSAGE_PAYLOAD => $data['payload']])
        ];

        $this->assertEquals($messageToCompare, $message->toArray());
        $this->assertEquals($data['topic'], $message->getTopic());
        if ($exception === null) {
            $encodedMessageToCompare = [
                PubSubMessage::EVENT_ATTRIBUTES => $attributes,
                PubSubMessage::EVENT_DATA       => json_encode(
                    array_merge($attributes, [PubSubMessage::MESSAGE_PAYLOAD => $data['payload']])
                )
            ];
            $this->assertEquals(
                $encodedMessageToCompare,
                $message->encode()
            );
        } else {
            $this->expectExceptionObject(
                new MessagePayloadEncodingException('Failed to encode message payload. Cause: [{Malformed UTF-8 characters, possibly incorrectly encoded}]')
            );
            $message->encode();
        }
    }

}