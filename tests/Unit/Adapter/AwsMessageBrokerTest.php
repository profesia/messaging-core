<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Test\Unit\Adapter;

use Aws\EventBridge\EventBridgeClient;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Profesia\MessagingCore\Adapter\AwsMessageBroker;
use Profesia\MessagingCore\Broking\Dto\Sending\BrokingBatchResponse;
use Profesia\MessagingCore\Broking\Dto\Sending\GroupedMessagesCollection;
use Profesia\MessagingCore\Test\Assets\Helper;

final class AwsMessageBrokerTest extends MockeryTestCase
{
    use Helper;

    public function testCanPublishSuccessfully(): void
    {
        $eventBridgeClient = Mockery::mock(EventBridgeClient::class);
        $broker            = new AwsMessageBroker($eventBridgeClient);

        $topicName         = 'testTopic';
        $messages          = self::createAwsMessages(3, ['topic' => $topicName]);
        $messageCollection = GroupedMessagesCollection::createFromMessages(...$messages);

        $eventBridgeClient
            ->shouldReceive('putEvents')
            ->once()
            ->withArgs(function ($args) use ($messages) {
                return count($args['Entries']) === count($messages);
            });

        $response = $broker->publish($messageCollection);

        $this->assertInstanceOf(BrokingBatchResponse::class, $response);
        $this->assertCount(count($messages), $response->getDispatchedMessages());

        foreach ($response->getDispatchedMessages() as $key => $dispatchedMessage) {
            $this->assertTrue($dispatchedMessage->wasDispatchedSuccessfully());
            $this->assertEquals($messages[$key]->getData(), $dispatchedMessage->getEventData());
            $this->assertEquals($messages[$key]->getAttributes(), $dispatchedMessage->getEventAttributes());
        }
    }

    public function testCanHandleRuntimeExceptionOnEncode(): void
    {
        $eventBridgeClient = Mockery::mock(EventBridgeClient::class);
        $broker            = new AwsMessageBroker($eventBridgeClient);

        $topicName = 'testTopic';
        $messages  = self::createAwsMessages(3, ['topic' => $topicName]);
        $messages  = [
            ...$messages,
            ...self::createAwsMessages(1, [
                'topic'   => $topicName,
                'payload' => [
                    'test-field' => pack('S4', 1974, 106, 28225, 32725),
                ],
            ], 3),
        ];

        $messageCollection = GroupedMessagesCollection::createFromMessages(...$messages);

        $eventBridgeClient
            ->shouldReceive('putEvents')
            ->once();

        $response = $broker->publish($messageCollection);

        $this->assertInstanceOf(BrokingBatchResponse::class, $response);

        $dispatchedMessages = $response->getDispatchedMessages();
        $this->assertCount(count($messages), $dispatchedMessages);

        foreach ($dispatchedMessages as $dispatchedMessage) {
            if ($dispatchedMessage->getEventData() === [3]) {
                $this->assertFalse($dispatchedMessage->wasDispatchedSuccessfully());
                $this->assertEquals(
                    "Failed to encode message payload. Cause: [{Malformed UTF-8 characters, possibly incorrectly encoded}]",
                    $dispatchedMessage->getDispatchReason()
                );
            } else {
                $this->assertTrue($dispatchedMessage->wasDispatchedSuccessfully());
            }
        }
    }
}
