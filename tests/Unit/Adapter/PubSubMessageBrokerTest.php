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
                $this->assertEquals($messages[$key], $dispatchedMessage->getMessage());
                $this->assertEquals('Testing exception', $dispatchedMessage->getDispatchReason());
            }

            $index++;
        }
    }
}
