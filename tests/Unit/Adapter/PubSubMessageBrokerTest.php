<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Test\Unit\Adapter;

use Google\Cloud\Core\Exception\GoogleException;
use Google\Cloud\PubSub\PubSubClient;
use Google\Cloud\PubSub\Topic;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Profesia\MessagingCore\Adapter\PubSubMessageBroker;
use Profesia\MessagingCore\Broking\Dto\GroupedMessagesCollection;
use Profesia\MessagingCore\Broking\Dto\Message;
use Profesia\MessagingCore\Test\Assets\Helper;

class PubSubMessageBrokerTest extends MockeryTestCase
{
    use Helper;

    public function testCanPublish(): void
    {
        /** @var PubSubClient|MockInterface $pubSubClient */
        $pubSubClient = Mockery::mock(PubSubClient::class);

        /** @var Topic|MockInterface $topic */
        $topic  = Mockery::mock(Topic::class);
        $broker = new PubSubMessageBroker(
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

        foreach ($messages as $message) {
            $topic
                ->shouldReceive('publish')
                ->once()
                ->withArgs(
                    [
                        $message->encode(),
                    ]
                );
        }

        $response = $broker->publish($messageCollection);

        foreach ($response->getDispatchedMessages() as $key => $dispatchedMessage) {
            $this->assertTrue($dispatchedMessage->wasDispatchedSuccessfully());
            $this->assertEquals($messages[$key]->toArray()[Message::EVENT_DATA], $dispatchedMessage->getEventData());
            $this->assertEquals($messages[$key]->toArray()[Message::EVENT_ATTRIBUTES], $dispatchedMessage->getEventAttributes());
        }
    }

    public function testCanHandleMixedDispatchResults(): void
    {
        /** @var PubSubClient|MockInterface $pubSubClient */
        $pubSubClient = Mockery::mock(PubSubClient::class);

        /** @var Topic|MockInterface $topic */
        $topic = Mockery::mock(Topic::class);

        $broker = new PubSubMessageBroker(
            $pubSubClient
        );

        $topicName = 'topic';
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

        $index = 1;
        foreach ($messages as $message) {
            if ($index !== 2) {
                $topic
                    ->shouldReceive('publish')
                    ->once()
                    ->withArgs(
                        [
                            $message->encode(),
                        ]
                    );
            } else {
                $topic
                    ->shouldReceive('publish')
                    ->once()
                    ->withArgs(
                        [
                            $message->encode(),
                        ]
                    )
                    ->andThrow(
                        new GoogleException('Testing exception')
                    );
            }

            $index++;
        }

        $response = $broker->publish($messageCollection);

        $index = 1;
        foreach ($response->getDispatchedMessages() as $key => $dispatchedMessage) {
            if ($index !== 2) {
                $this->assertTrue($dispatchedMessage->wasDispatchedSuccessfully());
                $this->assertEquals($messages[$key]->toArray()[Message::EVENT_DATA], $dispatchedMessage->getEventData());
                $this->assertEquals($messages[$key]->toArray()[Message::EVENT_ATTRIBUTES], $dispatchedMessage->getEventAttributes());
            } else {
                $this->assertFalse($dispatchedMessage->wasDispatchedSuccessfully());
                $this->assertEquals($messages[$key]->toArray()[Message::EVENT_DATA], $dispatchedMessage->getEventData());
                $this->assertEquals($messages[$key]->toArray()[Message::EVENT_ATTRIBUTES], $dispatchedMessage->getEventAttributes());
                $this->assertEquals('Testing exception', $dispatchedMessage->getDispatchReason());
            }

            $index++;
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

        $broker = new PubSubMessageBroker(
            $pubSubClient
        );

        $messages1 = self::createMessages(3, ['topic' => 'topic1']);
        $messages2 = self::createMessages(6, ['topic' => 'topic2']);
        $messages3 = self::createMessages(3, ['topic' => 'topic3']);

        $allMessages = array_merge($messages1, $messages2, $messages3);

        $messageCollection = GroupedMessagesCollection::createFromMessages(
            ...$allMessages
        );

        $topicMap = [
            'topic1' => $topic1,
            'topic2' => $topic2,
            'topic3' => $topic3,
        ];
        foreach ($messageCollection->getTopics() as $topicName) {
            $pubSubClient
                ->shouldReceive('topic')
                ->once()
                ->withArgs(
                    [
                        $topicName,
                    ]
                )
                ->andReturn(
                    $topicMap[$topicName]
                );
        }

        foreach ($messageCollection->getMessagesForTopic('topic1') as $message) {
            $topic1
                ->shouldReceive('publish')
                ->once()
                ->withArgs(
                    [
                        $message->encode(),
                    ]
                );
        }

        foreach ($messageCollection->getMessagesForTopic('topic2') as $message) {
            $topic2
                ->shouldReceive('publish')
                ->once()
                ->withArgs(
                    [
                        $message->encode(),
                    ]
                );
        }

        foreach ($messageCollection->getMessagesForTopic('topic3') as $message) {
            $topic3
                ->shouldReceive('publish')
                ->once()
                ->withArgs(
                    [
                        $message->encode(),
                    ]
                );
        }

        $response = $broker->publish($messageCollection);
        foreach ($response->getDispatchedMessages() as $key => $dispatchedMessage) {
            $this->assertTrue($dispatchedMessage->wasDispatchedSuccessfully());
            $this->assertEquals($dispatchedMessage->getEventData(), $allMessages[$key]->toArray()[Message::EVENT_DATA]);
            $this->assertEquals($dispatchedMessage->getEventAttributes(), $allMessages[$key]->toArray()[Message::EVENT_ATTRIBUTES]);
        }
    }

    public function testCanHandleExceptionInMultipleTopics(): void
    {
        /** @var PubSubClient|MockInterface $pubSubClient */
        $pubSubClient = Mockery::mock(PubSubClient::class);

        /** @var Topic|MockInterface $topic1 */
        $topic1 = Mockery::mock(Topic::class);

        /** @var Topic|MockInterface $topic2 */
        $topic2 = Mockery::mock(Topic::class);

        /** @var Topic|MockInterface $topic3 */
        $topic3 = Mockery::mock(Topic::class);

        $broker = new PubSubMessageBroker(
            $pubSubClient
        );

        $messages1 = self::createMessages(3, ['topic' => 'topic1']);
        $messages2 = self::createMessages(6, ['topic' => 'topic2']);
        $messages3 = self::createMessages(3, ['topic' => 'topic3']);

        $allMessages = array_merge($messages1, $messages2, $messages3);

        $messageCollection = GroupedMessagesCollection::createFromMessages(
            ...$allMessages
        );

        $topicMap = [
            'topic1' => $topic1,
            'topic2' => $topic2,
            'topic3' => $topic3,
        ];
        foreach ($messageCollection->getTopics() as $topicName) {
            $pubSubClient
                ->shouldReceive('topic')
                ->once()
                ->withArgs(
                    [
                        $topicName,
                    ]
                )
                ->andReturn(
                    $topicMap[$topicName]
                );
        }

        foreach ($messageCollection->getMessagesForTopic('topic1') as $message) {
            $topic1
                ->shouldReceive('publish')
                ->once()
                ->withArgs(
                    [
                        $message->encode(),
                    ]
                );
        }

        foreach ($messageCollection->getMessagesForTopic('topic2') as $message) {
            $topic2
                ->shouldReceive('publish')
                ->once()
                ->withArgs(
                    [
                        $message->encode(),
                    ]
                )->andThrow(
                    new GoogleException('Testing exception')
                );
        }

        foreach ($messageCollection->getMessagesForTopic('topic3') as $message) {
            $topic3
                ->shouldReceive('publish')
                ->once()
                ->withArgs(
                    [
                        $message->encode(),
                    ]
                );
        }

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
}
