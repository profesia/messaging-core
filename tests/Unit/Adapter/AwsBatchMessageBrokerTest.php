<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Test\Unit\Adapter;

use Aws\EventBridge\EventBridgeClient;
use Aws\Exception\AwsException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Profesia\MessagingCore\Adapter\AwsBatchMessageBroker;
use Profesia\MessagingCore\Broking\Dto\Sending\AwsMessage;
use Profesia\MessagingCore\Broking\Dto\Sending\BrokingBatchResponse;
use Profesia\MessagingCore\Broking\Dto\Sending\GroupedMessagesCollection;
use Profesia\MessagingCore\Broking\Dto\Sending\PubSubMessage;
use Profesia\MessagingCore\Test\Assets\Helper;

final class AwsBatchMessageBrokerTest extends MockeryTestCase
{
    use Helper;

    public function testCanPublish(): void
    {
        $eventBridgeClient = Mockery::mock(EventBridgeClient::class);
        $broker            = new AwsBatchMessageBroker($eventBridgeClient);

        $topicName         = 'testTopic';
        $messages          = self::createAwsMessages(3, ['topic' => $topicName]);
        $messageCollection = GroupedMessagesCollection::createFromMessages(...$messages);

        $eventBridgeClient
            ->shouldReceive('putEvents')
            ->once()
            ->withArgs(function (array $argument) use ($messages, $topicName) {
                return $argument === ['Entries' => array_map(static fn(AwsMessage $message) => [...$message->encode(), 'EventBusName' => $topicName], $messages)];
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

    public function testCanHandleMixedDispatchResults(): void
    {
        $eventBridgeClient = Mockery::mock(EventBridgeClient::class);
        $broker            = new AwsBatchMessageBroker($eventBridgeClient);

        $topicName         = 'testTopic';
        $messages          = self::createAwsMessages(3, ['topic' => $topicName]);
        $messageCollection = GroupedMessagesCollection::createFromMessages(...$messages);

        $awsException = new AwsException(
            'Testing exception',
            Mockery::mock('Aws\CommandInterface'),
        );

        $eventBridgeClient
            ->shouldReceive('putEvents')
            ->once()
            ->withArgs(function ($args) use ($messages) {
                return count($args['Entries']) === count($messages);
            })->andThrow($awsException);

        $response = $broker->publish($messageCollection);

        foreach ($response->getDispatchedMessages() as $key => $dispatchedMessage) {
            $this->assertFalse($dispatchedMessage->wasDispatchedSuccessfully());
            $this->assertEquals($dispatchedMessage->getEventData(), $messages[$key]->getData());
            $this->assertEquals($dispatchedMessage->getEventAttributes(), $messages[$key]->getAttributes());
            $this->assertEquals('Testing exception', $dispatchedMessage->getDispatchReason());
        }
    }

    public function testCanHandleMultipleTopics(): void
    {
        $eventBridgeClient = Mockery::mock(EventBridgeClient::class);
        $broker            = new AwsBatchMessageBroker($eventBridgeClient);

        $messages1 = self::createAwsMessages(3, ['topic' => 'topic1']);
        $messages2 = self::createAwsMessages(6, ['topic' => 'topic2']);
        $messages3 = self::createAwsMessages(3, ['topic' => 'topic3']);

        $allMessages       = array_merge($messages1, $messages2, $messages3);
        $messageCollection = GroupedMessagesCollection::createFromMessages(...$allMessages);

        $eventBridgeClient
            ->shouldReceive('putEvents')
            ->once()
            ->withArgs(function (array $argument) use ($messageCollection) {
                return $argument === ['Entries' => array_map(static fn(AwsMessage $message) => [...$message->encode(), 'EventBusName' => 'topic1'], $messageCollection->getMessagesForTopic('topic1'))];
            });

        $eventBridgeClient
            ->shouldReceive('putEvents')
            ->once()
            ->withArgs(function (array $argument) use ($messageCollection) {
                return $argument === ['Entries' => array_map(static fn(AwsMessage $message) => [...$message->encode(), 'EventBusName' => 'topic2'], $messageCollection->getMessagesForTopic('topic2'))];
            });

        $eventBridgeClient
            ->shouldReceive('putEvents')
            ->once()
            ->withArgs(function (array $argument) use ($messageCollection) {
                return $argument === ['Entries' => array_map(static fn(AwsMessage $message) => [...$message->encode(), 'EventBusName' => 'topic3'], $messageCollection->getMessagesForTopic('topic3'))];
            });

        $response = $broker->publish($messageCollection);

        foreach ($response->getDispatchedMessages() as $key => $dispatchedMessage) {
            $this->assertTrue($dispatchedMessage->wasDispatchedSuccessfully());
            $this->assertEquals($allMessages[$key]->getData(), $dispatchedMessage->getEventData());
            $this->assertEquals($allMessages[$key]->getAttributes(), $dispatchedMessage->getEventAttributes());
        }
    }

    public function testCanHandleAwsExceptionInMultipleTopics(): void
    {

    }

    public function testCanHandleExceptionOnEncode(): void
    {
        $eventBridgeClient = Mockery::mock(EventBridgeClient::class);
        $broker            = new AwsBatchMessageBroker($eventBridgeClient);

        $topicName = 'testTopic';
        $messages  = self::createAwsMessages(3, ['topic' => $topicName]);
        $messages  = [
            ...$messages,
            ...self::createAwsMessages(1, [
                'topic'   => $topicName,
                'data' => [
                    'data' => pack('S4', 1974, 106, 28225, 32725),
                ],
            ], 4),
        ];

        $messageCollection = GroupedMessagesCollection::createFromMessages(...$messages);

        $eventBridgeClient
            ->shouldReceive('putEvents')
            ->once();

        $response = $broker->publish($messageCollection);

        $dispatchedMessages = $response->getDispatchedMessages();
        $this->assertCount(count($messages), $dispatchedMessages);

        $errorKeys = [
            3
        ];
        foreach ($dispatchedMessages as $key => $dispatchedMessage) {
            if (in_array($key, $errorKeys) === true) {
                $this->assertFalse($dispatchedMessage->wasDispatchedSuccessfully());
                $this->assertEquals(
                    "Failed to encode message payload. Cause: [{Malformed UTF-8 characters, possibly incorrectly encoded}]",
                    $dispatchedMessage->getDispatchReason()
                );
            } else {
                $this->assertTrue($dispatchedMessage->wasDispatchedSuccessfully());
                $this->assertEquals($messages[$key]->getData(), $dispatchedMessage->getEventData());
                $this->assertEquals($messages[$key]->getAttributes(), $dispatchedMessage->getEventAttributes());
            }
        }
    }
}
