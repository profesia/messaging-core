<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Test\Integration\Broking\Dto\Sending;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Profesia\MessagingCore\Broking\Dto\Sending\AbstractMessage;
use Profesia\MessagingCore\Broking\Dto\Sending\AwsMessage;
use Profesia\MessagingCore\Broking\Exception\MessagePayloadEncodingException;
use Profesia\MessagingCore\Exception\AbstractRuntimeException;

class AwsMessageTest extends TestCase
{
    public function provideDataForDataGettingTest(): array
    {
        return [
            'event1'                  => [
                [
                    'topic'           => 'topic1',
                    'provider'        => 'provider1',
                    'eventType'       => 'eventType1',
                    'eventOccurredOn' => new DateTimeImmutable(),
                    'correlationId'   => 'correlationId1',
                    'payload'         => [
                        'key' => 'value',
                    ],
                    'resource'        => 'resource1',
                    'objectId'        => 'objectId1',
                    'subscribeName'   => 'subscribeName1',
                ],
            ],
            'event-with-encode-error' => [
                [
                    'topic'           => 'topic1',
                    'provider'        => 'provider1',
                    'eventType'       => 'eventType1',
                    'eventOccurredOn' => new DateTimeImmutable(),
                    'correlationId'   => 'correlationId1',
                    'payload'       => [
                        'test-field' => pack('S4', 1974, 106, 28225, 32725),
                    ],
                    'resource'        => 'resource1',
                    'objectId'        => 'objectId1',
                    'subscribeName'   => 'subscribeName1',
                ],
                new MessagePayloadEncodingException('Failed to encode message payload. Cause: [{Malformed UTF-8 characters, possibly incorrectly encoded}]'),
            ],
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
        $message = new AwsMessage(
            $data['resource'],
            $data['eventType'],
            $data['provider'],
            $data['objectId'],
            $data['eventOccurredOn'],
            $data['correlationId'],
            $data['subscribeName'],
            $data['topic'],
            $data['payload'],
        );

        $this->assertEquals($data['topic'], $message->getTopic());

        $this->assertEquals([
            AbstractMessage::EVENT_PROVIDER       => $data['provider'],
            AbstractMessage::EVENT_TYPE           => $data['eventType'],
            AbstractMessage::EVENT_CORRELATION_ID => $data['correlationId'],
            AbstractMessage::EVENT_OCCURRED_ON    => $data['eventOccurredOn']->format('Y-m-d H:i:s.u'),
            AbstractMessage::EVENT_RESOURCE       => $data['resource'],
            AbstractMessage::EVENT_OBJECT_ID      => $data['objectId'],
            AbstractMessage::EVENT_SUBSCRIBE_NAME => $data['subscribeName'],
            AbstractMessage::MESSAGE_PAYLOAD      => $data['payload'],
        ], $message->getData());

        $this->assertEquals([
            AwsMessage::SOURCE      => $data['provider'],
            AwsMessage::DETAIL_TYPE => $data['eventType'],
            AwsMessage::TIME        => $data['eventOccurredOn']->format('Y-m-d H:i:s.u'),
            AwsMessage::DETAIL      => [
                AbstractMessage::EVENT_PROVIDER       => $data['provider'],
                AbstractMessage::EVENT_TYPE           => $data['eventType'],
                AbstractMessage::EVENT_CORRELATION_ID => $data['correlationId'],
                AbstractMessage::EVENT_OCCURRED_ON    => $data['eventOccurredOn']->format('Y-m-d H:i:s.u'),
                AbstractMessage::EVENT_RESOURCE       => $data['resource'],
                AbstractMessage::EVENT_OBJECT_ID      => $data['objectId'],
                AbstractMessage::EVENT_SUBSCRIBE_NAME => $data['subscribeName'],
                AbstractMessage::MESSAGE_PAYLOAD      => $data['payload'],
            ],
        ], $message->toArray());

        if ($exception === null) {
            $encodedMessageToCompare = [
                AwsMessage::SOURCE      => $data['provider'],
                AwsMessage::DETAIL_TYPE => $data['eventType'],
                AwsMessage::TIME        => $data['eventOccurredOn']->format('Y-m-d H:i:s.u'),
                AwsMessage::DETAIL      => json_encode(
                    [
                        AbstractMessage::EVENT_PROVIDER       => $data['provider'],
                        AbstractMessage::EVENT_TYPE           => $data['eventType'],
                        AbstractMessage::EVENT_CORRELATION_ID => $data['correlationId'],
                        AbstractMessage::EVENT_OCCURRED_ON    => $data['eventOccurredOn']->format('Y-m-d H:i:s.u'),
                        AbstractMessage::EVENT_RESOURCE       => $data['resource'],
                        AbstractMessage::EVENT_OBJECT_ID      => $data['objectId'],
                        AbstractMessage::EVENT_SUBSCRIBE_NAME => $data['subscribeName'],
                        AbstractMessage::MESSAGE_PAYLOAD      => $data['payload'],
                    ],
                ),
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
