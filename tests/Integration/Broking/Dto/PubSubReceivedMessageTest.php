<?php

namespace Profesia\MessagingCore\Test\Integration\Broking\Dto;

use PHPUnit\Framework\TestCase;
use Profesia\MessagingCore\Broking\Dto\Message;
use Profesia\MessagingCore\Broking\Dto\PubSubReceivedMessage;
use Profesia\MessagingCore\Broking\Exception\ReceivedMessageDecodingFailedException;
use Profesia\MessagingCore\Broking\Exception\ReceivedMessageBadStructureException;

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
        $dataKey       = Message::EVENT_DATA;
        $attributesKey = Message::EVENT_ATTRIBUTES;
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
                Message::EVENT_TYPE,
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
            Message::EVENT_DATA       => base64_encode(
                json_encode(
                    $data
                )
            ),
            Message::EVENT_ATTRIBUTES => [
                Message::EVENT_TYPE           => $eventType,
                Message::EVENT_SUBSCRIBE_NAME => $subscriberName
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

        $decodedMessage                      = $message;
        $decodedMessage[Message::EVENT_DATA] = json_decode(base64_decode($message[Message::EVENT_DATA]), true);
        $this->assertEquals($decodedMessage, $receivedMessage->getDecodedMessage());
    }
}