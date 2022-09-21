<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Unit\Adapter;

use DateTimeImmutable;
use Google\Cloud\Core\Exception\GoogleException;
use Google\Cloud\PubSub\PubSubClient;
use Google\Cloud\PubSub\Topic;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Profesia\MessagingCore\Adapter\PubSubMessageBroker;
use Profesia\MessagingCore\Broking\Dto\Message;
use Profesia\MessagingCore\Broking\Dto\MessageCollection;

class PubSubMessageBrokerTest extends MockeryTestCase
{
    public function testCanPublish(): void
    {
        /** @var PubSubClient|MockInterface $pubSubClient */
        $pubSubClient = Mockery::mock(PubSubClient::class);

        /** @var Topic|MockInterface $topic */
        $topic = Mockery::mock(Topic::class);
        $broker = new PubSubMessageBroker(
            $pubSubClient
        );

        $channel  = 'channel';
        $messages = [
            new Message(
                'resource1',
                'eventType1',
                'provider1',
                'objectId1',
                new DateTimeImmutable(),
                'correlationId',
                'target1',
                [
                    'data' => 1,
                ]
            ),
            new Message(
                'resource2',
                'eventType2',
                'provider2',
                'objectId2',
                new DateTimeImmutable(),
                'correlationId',
                'target2',
                [
                    'data' => 2,
                ]
            ),
            new Message(
                'resource3',
                'eventType3',
                'provider3',
                'objectId3',
                new DateTimeImmutable(),
                'correlationId',
                'target3',
                [
                    'data' => 3,
                ]
            ),
        ];

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
            ->shouldReceive('exists')
            ->once()
            ->andReturn(true);

        foreach ($messages as $message) {
            $topic
                ->shouldReceive('publish')
                ->once()
                ->withArgs(
                    [
                        $message->toArray(),
                    ]
                );
        }

        $response = $broker->publish($messageCollection);

        foreach ($response->getDispatchedMessages() as $key => $dispatchedMessage) {
            $this->assertTrue($dispatchedMessage->wasDispatchedSuccessfully());
            $this->assertEquals($dispatchedMessage->getMessage(), $messages[$key]);
        }
    }

    public function testCanFindNonExistingTopic(): void
    {
        /** @var PubSubClient|MockInterface $pubSubClient */
        $pubSubClient = Mockery::mock(PubSubClient::class);

        /** @var Topic|MockInterface $topic */
        $topic = Mockery::mock(Topic::class);
        $broker = new PubSubMessageBroker(
            $pubSubClient
        );

        $channel  = 'channel';
        $messages = [
            new Message(
                'resource1',
                'eventType1',
                'provider1',
                'objectId1',
                new DateTimeImmutable(),
                'correlationId',
                'target1',
                [
                    'data' => 1,
                ]
            ),
            new Message(
                'resource2',
                'eventType2',
                'provider2',
                'objectId2',
                new DateTimeImmutable(),
                'correlationId',
                'target2',
                [
                    'data' => 2,
                ]
            ),
            new Message(
                'resource3',
                'eventType3',
                'provider3',
                'objectId3',
                new DateTimeImmutable(),
                'correlationId',
                'target3',
                [
                    'data' => 3,
                ]
            ),
        ];
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
            ->shouldReceive('exists')
            ->once()
            ->andReturn(false);


        $response = $broker->publish(
            MessageCollection::createFromMessagesAndChannel(
                $channel,
                ...$messages
            )
        );

        foreach ($response->getDispatchedMessages() as $key => $dispatchedMessage) {
            $this->assertFalse($dispatchedMessage->wasDispatchedSuccessfully());
            $this->assertEquals($dispatchedMessage->getMessage(), $messages[$key]);
            $this->assertEquals($dispatchedMessage->getDispatchReason(), "Topic with name: [{$channel}] does not exist");
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

        $channel  = 'channel';
        $messages = [
            new Message(
                'resource1',
                'eventType1',
                'provider1',
                'objectId1',
                new DateTimeImmutable(),
                'correlationId',
                'target1',
                [
                    'data' => 1,
                ]
            ),
            new Message(
                'resource2',
                'eventType2',
                'provider2',
                'objectId2',
                new DateTimeImmutable(),
                'correlationId',
                'target2',
                [
                    'data' => 2,
                ]
            ),
            new Message(
                'resource3',
                'eventType3',
                'provider3',
                'objectId3',
                new DateTimeImmutable(),
                'correlationId',
                'target3',
                [
                    'data' => 3,
                ]
            ),
        ];

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
            ->shouldReceive('exists')
            ->once()
            ->andReturn(true);

        $index = 1;
        foreach ($messages as $message) {
            if ($index !== 2) {
                $topic
                    ->shouldReceive('publish')
                    ->once()
                    ->withArgs(
                        [
                            $message->toArray(),
                        ]
                    );
            } else {
                $topic
                    ->shouldReceive('publish')
                    ->once()
                    ->withArgs(
                        [
                            $message->toArray(),
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
                $this->assertEquals($dispatchedMessage->getMessage(), $messages[$key]);
            } else {
                $this->assertFalse($dispatchedMessage->wasDispatchedSuccessfully());
                $this->assertEquals($dispatchedMessage->getMessage(), $messages[$key]);
                $this->assertEquals($dispatchedMessage->getDispatchReason(), 'Testing exception');
            }

            $index++;
        }
    }
}
