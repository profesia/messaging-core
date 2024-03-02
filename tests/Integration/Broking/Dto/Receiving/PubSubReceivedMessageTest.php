<?php

namespace Profesia\MessagingCore\Test\Integration\Broking\Dto\Receiving;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Profesia\MessagingCore\Broking\Dto\Receiving\PubSubReceivedMessage;
use Profesia\MessagingCore\Broking\Dto\Sending\PubSubMessage;
use Profesia\MessagingCore\Broking\Exception\ReceivedMessageBadStructureException;
use Profesia\MessagingCore\Broking\Exception\ReceivedMessageDecodingFailedException;

class PubSubReceivedMessageTest extends TestCase
{
    public function testCanDetectInvalidJsonInEnvelope(): void
    {
        $invalidJson = '{"a":';
        json_decode($invalidJson, true);
        $exceptionMessage = json_last_error_msg();

        $this->expectExceptionObject(new ReceivedMessageDecodingFailedException("Failed to decode received message. Cause: [$exceptionMessage]"));
        PubSubReceivedMessage::createFromJsonString($invalidJson);
    }

    public function testCanDetectInvalidJsonInMessageData(): void
    {
        $invalidData = base64_encode('{"a":');
        $invalidJson = "{\"message\":{\"attributes\":[], \"data\":\"{$invalidData}\"}}";
        json_decode(base64_decode($invalidData), true);
        $exceptionMessage = json_last_error_msg();


        $this->expectExceptionObject(new ReceivedMessageDecodingFailedException("Failed to decode received message. Cause: [$exceptionMessage]"));
        PubSubReceivedMessage::createFromJsonString($invalidJson);
    }

    public function provideDataForStructureTests(): array
    {
        $dataKey       = PubSubMessage::EVENT_DATA;
        $attributesKey = PubSubMessage::EVENT_ATTRIBUTES;
        return [
            'missing message offset'    => [
                json_encode([]),
                'message',
                ''
            ],
            'missing data offset'       => [
                json_encode(['message' => [$attributesKey => []]]),
                $dataKey,
                'message'
            ],
            'missing attributes offset' => [
                json_encode(['message' => [$dataKey => base64_encode(json_encode([]))]]),
                $attributesKey,
                'message'
            ],
            'missing event type offset' => [
                json_encode(['message' => [$dataKey => base64_encode(json_encode([])), $attributesKey => []]]),
                PubSubMessage::EVENT_TYPE,
                'message, attributes'
            ]
        ];
    }

    /**
     * @param string $json
     * @param string $missingOffset
     * @param string $path
     * @return void
     *
     * @dataProvider provideDataForStructureTests
     */
    public function testCanDetectBadMessageStructure(string $json, string $missingOffset, string $path): void
    {
        $this->expectExceptionObject(new ReceivedMessageBadStructureException("Missing offset: [$missingOffset] in path: [$path]"));
        PubSubReceivedMessage::createFromJsonString($json);
    }

    public function testCanDetectInvalidRawData(): void
    {
        $this->expectExceptionObject(new ReceivedMessageBadStructureException("Missing offset: [eventType] in attributes"));
        PubSubReceivedMessage::createFromRaw([], []);
    }

    public function testCanCreateFromRawInput(): void
    {
        $attributes =             [
            PubSubMessage::EVENT_RESOURCE       => 'resource1',
            PubSubMessage::EVENT_TYPE           => 'eventType1',
            PubSubMessage::EVENT_PROVIDER       => 'provider1',
            PubSubMessage::EVENT_OBJECT_ID      => 'objectId1',
            PubSubMessage::EVENT_OCCURRED_ON    => new DateTimeImmutable(),
            PubSubMessage::EVENT_CORRELATION_ID => 'correlationId1',
            PubSubMessage::EVENT_SUBSCRIBE_NAME => 'subscribeName1',
        ];
        $payload = 'test';
        $message = PubSubReceivedMessage::createFromRaw(
            $attributes,
            [
                PubSubMessage::MESSAGE_PAYLOAD => $payload
            ]
        );

        $this->assertEquals($attributes[PubSubMessage::EVENT_TYPE], $message->getEventType());
        $this->assertEquals($attributes[PubSubMessage::EVENT_SUBSCRIBE_NAME], $message->getSubscribeName());
        $this->assertEquals([PubSubMessage::EVENT_ATTRIBUTES => $attributes, PubSubMessage::EVENT_DATA => [PubSubMessage::MESSAGE_PAYLOAD => $payload]], $message->getDecodedMessage());
    }

    public function testCanGetValues(): void
    {
        $data           = [
            'a' => 1,
            'b' => 2,
            'c' => 3
        ];
        $eventType      = 'TestEventType';
        $subscriberName = 'SubscriberName';

        $message = [
            PubSubMessage::EVENT_DATA       => base64_encode(
                json_encode(
                    $data
                )
            ),
            PubSubMessage::EVENT_ATTRIBUTES => [
                PubSubMessage::EVENT_TYPE           => $eventType,
                PubSubMessage::EVENT_SUBSCRIBE_NAME => $subscriberName
            ]
        ];
        $json    = json_encode(
            [
                'message' => $message
            ]
        );

        $receivedMessage = PubSubReceivedMessage::createFromJsonString($json);
        $this->assertEquals($eventType, $receivedMessage->getEventType());
        $this->assertEquals($subscriberName, $receivedMessage->getSubscribeName());

        $decodedMessage                            = $message;
        $decodedMessage[PubSubMessage::EVENT_DATA] = json_decode(base64_decode($message[PubSubMessage::EVENT_DATA]), true);
        $this->assertEquals($decodedMessage, $receivedMessage->getDecodedMessage());
    }
}