<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Test\Unit\Adapter;

use Google\Cloud\Core\Exception\GoogleException;
use Google\Cloud\PubSub\PubSubClient;
use Google\Cloud\PubSub\Topic;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Profesia\MessagingCore\Adapter\PubSubBatchMessageBroker;
use Profesia\MessagingCore\Broking\Dto\Sending\GroupedMessagesCollection;
use Profesia\MessagingCore\Broking\Dto\Sending\Message;
use Profesia\MessagingCore\Test\Assets\Helper;

class PubSubBatchMessageBrokerTest extends MockeryTestCase
{
    use Helper;

    public function testCanPublish(): void
    {
        /** @var PubSubClient|MockInterface $pubSubClient */
        $pubSubClient = Mockery::mock(PubSubClient::class);

        /** @var Topic|MockInterface $topic */
        $topic  = Mockery::mock(Topic::class);
        $broker = new PubSubBatchMessageBroker(
            $pubSubClient
        );

        $topicName = 'topicName';
        $messages  = static::createMessages(3, [
            'topic' => $topicName
        ]);

        $messageCollection = GroupedMessagesCollection::createFromMessages(
            ...$messages
        );

        $pubSubClient
            ->shouldReceive('topic')
            ->once()
            ->withArgs(
                [
                    $topicName,
                ]
            )
            ->andReturn(
                $topic
            );

        $topic
            ->shouldReceive('publishBatch')
            ->once()
            ->withArgs(
                [
                    array_map(static fn(Message $message) => $message->encode(), $messageCollection->getMessagesForTopic($topicName)),
                ]
            );

        $response = $broker->publish($messageCollection);

        foreach ($response->getDispatchedMessages() as $key => $dispatchedMessage) {
            $this->assertTrue($dispatchedMessage->wasDispatchedSuccessfully());
            $this->assertEquals($dispatchedMessage->getEventData(), $messages[$key]->toArray()[Message::EVENT_DATA]);
            $this->assertEquals($dispatchedMessage->getEventAttributes(), $messages[$key]->toArray()[Message::EVENT_ATTRIBUTES]);
        }
    }

    public function testCanHandleMixedDispatchResults(): void
    {
        /** @var PubSubClient|MockInterface $pubSubClient */
        $pubSubClient = Mockery::mock(PubSubClient::class);

        /** @var Topic|MockInterface $topic */
        $topic = Mockery::mock(Topic::class);

        $broker = new PubSubBatchMessageBroker(
            $pubSubClient
        );

        $topicName = 'topicName';
        $messages  = static::createMessages(3, [
            'topic' => $topicName
        ]);

        $messageCollection = GroupedMessagesCollection::createFromMessages(
            ...$messages
        );

        $pubSubClient
            ->shouldReceive('topic')
            ->once()
            ->withArgs(
                [
                    $topicName,
                ]
            )
            ->andReturn(
                $topic
            );

        $topic
            ->shouldReceive('publishBatch')
            ->once()
            ->withArgs(
                [
                    array_map(static fn(Message $message) => $message->encode(), $messageCollection->getMessagesForTopic($topicName)),
                ]
            )
            ->andThrow(new GoogleException('Testing exception'));

        $response = $broker->publish($messageCollection);

        foreach ($response->getDispatchedMessages() as $key => $dispatchedMessage) {
            $this->assertFalse($dispatchedMessage->wasDispatchedSuccessfully());
            $this->assertEquals($dispatchedMessage->getEventData(), $messages[$key]->toArray()[Message::EVENT_DATA]);
            $this->assertEquals($dispatchedMessage->getEventAttributes(), $messages[$key]->toArray()[Message::EVENT_ATTRIBUTES]);
            $this->assertEquals('Testing exception', $dispatchedMessage->getDispatchReason());
        }
    }

    public function testCanHandleMultipleTopics(): void
    {
        /** @var PubSubClient|MockInterface $pubSubClient */
        $pubSubClient = Mockery::mock(PubSubClient::class);

        /** @var Topic|MockInterface $topic1 */
        $topic1 = Mockery::mock(Topic::class);

        /** @var Topic|MockInterface $topic2 */
        $topic2 = Mockery::mock(Topic::class);

        /** @var Topic|MockInterface $topic3 */
        $topic3 = Mockery::mock(Topic::class);

        $broker = new PubSubBatchMessageBroker(
            $pubSubClient
        );

        $messages1 = self::createMessages(3, ['topic' => 'topic1']);
        $messages2 = self::createMessages(6, ['topic' => 'topic2']);
        $messages3 = self::createMessages(3, ['topic' => 'topic3']);

        $allMessages = array_merge($messages1, $messages2, $messages3);

        $pubSubClient
            ->shouldReceive('topic')
            ->once()
            ->withArgs(
                [
                    'topic1',
                ]
            )
            ->andReturn(
                $topic1
            );

        $pubSubClient
            ->shouldReceive('topic')
            ->once()
            ->withArgs(
                [
                    'topic2',
                ]
            )
            ->andReturn(
                $topic2
            );

        $pubSubClient
            ->shouldReceive('topic')
            ->once()
            ->withArgs(
                [
                    'topic3',
                ]
            )
            ->andReturn(
                $topic3
            );

        $messageCollection = GroupedMessagesCollection::createFromMessages(
            ...$allMessages
        );

        $topic1
            ->shouldReceive('publishBatch')
            ->once()
            ->withArgs(
                [
                    array_map(static fn(Message $message) => $message->encode(), $messageCollection->getMessagesForTopic('topic1')),
                ]
            );

        $topic2
            ->shouldReceive('publishBatch')
            ->once()
            ->withArgs(
                [
                    array_map(static fn(Message $message) => $message->encode(), $messageCollection->getMessagesForTopic('topic2')),
                ]
            );

        $topic3
            ->shouldReceive('publishBatch')
            ->once()
            ->withArgs(
                [
                    array_map(static fn(Message $message) => $message->encode(), $messageCollection->getMessagesForTopic('topic3')),
                ]
            );

        $response = $broker->publish($messageCollection);
        foreach ($response->getDispatchedMessages() as $key => $dispatchedMessage) {
            $this->assertTrue($dispatchedMessage->wasDispatchedSuccessfully());
            $this->assertEquals($dispatchedMessage->getEventData(), $allMessages[$key]->toArray()[Message::EVENT_DATA]);
            $this->assertEquals($dispatchedMessage->getEventAttributes(), $allMessages[$key]->toArray()[Message::EVENT_ATTRIBUTES]);
        }
    }

    public function testCanHandleGoogleExceptionInMultipleTopics(): void
    {
        /** @var PubSubClient|MockInterface $pubSubClient */
        $pubSubClient = Mockery::mock(PubSubClient::class);

        /** @var Topic|MockInterface $topic1 */
        $topic1 = Mockery::mock(Topic::class);

        /** @var Topic|MockInterface $topic2 */
        $topic2 = Mockery::mock(Topic::class);

        /** @var Topic|MockInterface $topic3 */
        $topic3 = Mockery::mock(Topic::class);

        $broker = new PubSubBatchMessageBroker(
            $pubSubClient
        );

        $messages1 = self::createMessages(3, ['topic' => 'topic1']);
        $messages2 = self::createMessages(6, ['topic' => 'topic2']);
        $messages3 = self::createMessages(3, ['topic' => 'topic3']);

        $allMessages = array_merge($messages1, $messages2, $messages3);

        $pubSubClient
            ->shouldReceive('topic')
            ->once()
            ->withArgs(
                [
                    'topic1',
                ]
            )
            ->andReturn(
                $topic1
            );

        $pubSubClient
            ->shouldReceive('topic')
            ->once()
            ->withArgs(
                [
                    'topic2',
                ]
            )
            ->andReturn(
                $topic2
            );

        $pubSubClient
            ->shouldReceive('topic')
            ->once()
            ->withArgs(
                [
                    'topic3',
                ]
            )
            ->andReturn(
                $topic3
            );

        $messageCollection = GroupedMessagesCollection::createFromMessages(
            ...$allMessages
        );

        $topic1
            ->shouldReceive('publishBatch')
            ->once()
            ->withArgs(
                [
                    array_map(static fn(Message $message) => $message->encode(), $messageCollection->getMessagesForTopic('topic1')),
                ]
            );

        $topic2
            ->shouldReceive('publishBatch')
            ->once()
            ->withArgs(
                [
                    array_map(static fn(Message $message) => $message->encode(), $messageCollection->getMessagesForTopic('topic2')),
                ]
            )->andThrow(new GoogleException('Testing exception'));

        $topic3
            ->shouldReceive('publishBatch')
            ->once()
            ->withArgs(
                [
                    array_map(static fn(Message $message) => $message->encode(), $messageCollection->getMessagesForTopic('topic3')),
                ]
            );

        $response = $broker->publish($messageCollection);
        foreach ($response->getDispatchedMessages() as $key => $dispatchedMessage) {
            if ($dispatchedMessage->getTopic() !== 'topic2') {
                $this->assertTrue($dispatchedMessage->wasDispatchedSuccessfully());
            } else {
                $this->assertFalse($dispatchedMessage->wasDispatchedSuccessfully());
            }
            $this->assertEquals($dispatchedMessage->getEventData(), $allMessages[$key]->toArray()[Message::EVENT_DATA]);
            $this->assertEquals($dispatchedMessage->getEventAttributes(), $allMessages[$key]->toArray()[Message::EVENT_ATTRIBUTES]);
        }
    }

    public function testCanHandleAbstractRuntimeExceptionInMultipleTopics(): void
    {
        /** @var PubSubClient|MockInterface $pubSubClient */
        $pubSubClient = Mockery::mock(PubSubClient::class);

        /** @var Topic|MockInterface $topic1 */
        $topic1 = Mockery::mock(Topic::class);

        /** @var Topic|MockInterface $topic2 */
        $topic2 = Mockery::mock(Topic::class);

        /** @var Topic|MockInterface $topic3 */
        $topic3 = Mockery::mock(Topic::class);

        $broker = new PubSubBatchMessageBroker(
            $pubSubClient
        );

        $messages1 = array_merge(
            self::createMessages(1, ['topic' => 'topic1'], 1),
            self::createMessages(1, ['topic' => 'topic1', 'data' => ['data' => pack('S4', 1974, 106, 28225, 32725)]], 2),
            self::createMessages(1, ['topic' => 'topic1'], 3),
        );

        $messages2 = array_merge(
            self::createMessages(2, ['topic' => 'topic2']),
            self::createMessages(2, ['topic' => 'topic2', 'data' => ['data' => pack('S4', 1974, 106, 28225, 32725)]], 3),
            self::createMessages(2, ['topic' => 'topic2'], 5),
        );

        $messages3 = array_merge(
            self::createMessages(1, ['topic' => 'topic3'], 1),
            self::createMessages(1, ['topic' => 'topic3'], 2),
            self::createMessages(1, ['topic' => 'topic3', 'data' => ['data' => pack('S4', 1974, 106, 28225, 32725)]], 3),
        );

        $allMessages = array_merge($messages1, $messages2, $messages3);

        $pubSubClient
            ->shouldReceive('topic')
            ->once()
            ->withArgs(
                [
                    'topic1',
                ]
            )
            ->andReturn(
                $topic1
            );

        $pubSubClient
            ->shouldReceive('topic')
            ->once()
            ->withArgs(
                [
                    'topic2',
                ]
            )
            ->andReturn(
                $topic2
            );

        $pubSubClient
            ->shouldReceive('topic')
            ->once()
            ->withArgs(
                [
                    'topic3',
                ]
            )
            ->andReturn(
                $topic3
            );

        $messageCollection = GroupedMessagesCollection::createFromMessages(
            ...$allMessages
        );

        $topic1
            ->shouldReceive('publishBatch')
            ->once()
            ->withArgs(
                [
                    array_map(
                        static fn(Message $message) => $message->encode(),
                        array_filter(
                            $messageCollection->getMessagesForTopic('topic1'),
                            static fn(Message $message, int $key) => $key !== 1,
                            ARRAY_FILTER_USE_BOTH
                        )
                    ),
                ]
            );

        $topic2
            ->shouldReceive('publishBatch')
            ->once()
            ->withArgs(
                [
                    array_map(
                        static fn(Message $message) => $message->encode(),
                        array_filter(
                            $messageCollection->getMessagesForTopic('topic2'),
                            static fn(Message $message, int $key) => ($key !== 2 && $key !== 3),
                            ARRAY_FILTER_USE_BOTH
                        )
                    ),
                ]
            );

        $topic3
            ->shouldReceive('publishBatch')
            ->once()
            ->withArgs(
                [
                    array_map(
                        static fn(Message $message) => $message->encode(),
                        array_filter(
                            $messageCollection->getMessagesForTopic('topic3'),
                            static fn(Message $message, int $key) => $key !== 2,
                            ARRAY_FILTER_USE_BOTH
                        )
                    ),
                ]
            );

        $response  = $broker->publish($messageCollection);
        $errorKeys = [
            1,
            5,
            6,
            11
        ];
        foreach ($response->getDispatchedMessages() as $key => $dispatchedMessage) {
            $this->assertEquals(in_array($key, $errorKeys) === false, $dispatchedMessage->wasDispatchedSuccessfully());
            $this->assertEquals($dispatchedMessage->getEventAttributes(), $allMessages[$key]->toArray()[Message::EVENT_ATTRIBUTES]);
            $this->assertEquals($dispatchedMessage->getEventData(), $allMessages[$key]->toArray()[Message::EVENT_DATA]);

        }
    }
}
