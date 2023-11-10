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
use Profesia\MessagingCore\Broking\Dto\GroupedMessagesCollection;
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
                    $messageCollection->getMessagesDataForTopic($topicName),
                ]
            );

        $response = $broker->publish($messageCollection);

        foreach ($response->getDispatchedMessages() as $key => $dispatchedMessage) {
            $this->assertTrue($dispatchedMessage->wasDispatchedSuccessfully());
            $this->assertEquals($dispatchedMessage->getMessage(), $messages[$key]);
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
                    $messageCollection->getMessagesDataForTopic($topicName),
                ]
            )
            ->andThrow(new GoogleException('Testing exception'));

        $response = $broker->publish($messageCollection);

        foreach ($response->getDispatchedMessages() as $key => $dispatchedMessage) {
            $this->assertFalse($dispatchedMessage->wasDispatchedSuccessfully());
            $this->assertEquals($messages[$key], $dispatchedMessage->getMessage());
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
                    $messageCollection->getMessagesDataForTopic('topic1'),
                ]
            );

        $topic2
            ->shouldReceive('publishBatch')
            ->once()
            ->withArgs(
                [
                    $messageCollection->getMessagesDataForTopic('topic2'),
                ]
            );

        $topic3
            ->shouldReceive('publishBatch')
            ->once()
            ->withArgs(
                [
                    $messageCollection->getMessagesDataForTopic('topic3'),
                ]
            );

        $response = $broker->publish($messageCollection);
        foreach ($response->getDispatchedMessages() as $key => $dispatchedMessage) {
            $this->assertTrue($dispatchedMessage->wasDispatchedSuccessfully());
            $this->assertEquals($dispatchedMessage->getMessage(), $allMessages[$key]);
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
                    $messageCollection->getMessagesDataForTopic('topic1'),
                ]
            );

        $topic2
            ->shouldReceive('publishBatch')
            ->once()
            ->withArgs(
                [
                    $messageCollection->getMessagesDataForTopic('topic2'),
                ]
            )->andThrow(new GoogleException('Testing exception'));

        $topic3
            ->shouldReceive('publishBatch')
            ->once()
            ->withArgs(
                [
                    $messageCollection->getMessagesDataForTopic('topic3'),
                ]
            );

        $response = $broker->publish($messageCollection);
        foreach ($response->getDispatchedMessages() as $key => $dispatchedMessage) {
            if ($dispatchedMessage->getMessage()->getTopic() !== 'topic2') {
                $this->assertTrue($dispatchedMessage->wasDispatchedSuccessfully());
            } else {
                $this->assertFalse($dispatchedMessage->wasDispatchedSuccessfully());
            }
            $this->assertEquals($dispatchedMessage->getMessage(), $allMessages[$key]);
        }
    }
}
