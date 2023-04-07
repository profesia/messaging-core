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
use Profesia\MessagingCore\Broking\Dto\MessageCollection;
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

        $channel  = 'channel';
        $messages = static::createMessages(3);

        $messageCollection = MessageCollection::createFromMessagesAndChannel(
               $channel,
            ...$messages
        );

        $pubSubClient
            ->shouldReceive('topic')
            ->once()
            ->withArgs(
                [
                    $channel,
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
                    $messageCollection->getMessagesData(),
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

        $channel  = 'channel';
        $messages = static::createMessages(3);

        $messageCollection = MessageCollection::createFromMessagesAndChannel(
               $channel,
            ...$messages
        );

        $pubSubClient
            ->shouldReceive('topic')
            ->once()
            ->withArgs(
                [
                    $channel,
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
                    $messageCollection->getMessagesData(),
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
}
