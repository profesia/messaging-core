<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Test\Unit\Broking\Decorator;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Profesia\MessagingCore\Broking\Decorator\TopicFilteringMessagesLogger;
use Profesia\MessagingCore\Broking\Dto\Sending\AbstractMessage;
use Profesia\MessagingCore\Broking\Dto\Sending\BrokingBatchResponse;
use Profesia\MessagingCore\Broking\Dto\Sending\GroupedMessagesCollection;
use Profesia\MessagingCore\Broking\Dto\Sending\Message;
use Profesia\MessagingCore\Broking\Dto\Sending\MessageInterface;
use Profesia\MessagingCore\Broking\MessageBrokerInterface;
use Profesia\MessagingCore\Test\Assets\Helper;
use Psr\Log\LoggerInterface;

class TopicFilteringMessagesLoggerTest extends MockeryTestCase
{
    use Helper;

    public function provideDataFroFiltering(): array
    {
        return [
            'topic-name-lower-case' => [
                static::createMessages(11),
                'topic1',
            ],
            'topic-name-upper-case' => [
                static::createMessages(11),
                'TOPIC2',
            ],
            'topic-name-not-matching' => [
                static::createMessages(5),
                'not-matching',
            ],
            /*[
                static::createMessages(11),
                'topic1'
            ],*/
        ];
    }

    /**
     * @param array $allMessages
     * @param string $targetSubstring
     * @return void
     *
     * @dataProvider provideDataFroFiltering
     */
    public function testCanFilterMessages(array $allMessages, string $targetSubstring): void
    {
        $collection       = GroupedMessagesCollection::createFromMessages(
            ...$allMessages
        );
        $expectedResponse = BrokingBatchResponse::createForMessagesWithBatchStatus(
            true,
            null,
            ...$allMessages
        );

        /** @var MessageBrokerInterface|MockInterface $broker */
        $broker = Mockery::mock(MessageBrokerInterface::class);
        $broker
            ->shouldReceive('publish')
            ->once()
            ->withArgs(
                [
                    $collection,
                ]
            )->andReturn(
                $expectedResponse
            );

        $projectName      = 'projectName';
        $filteredMessages = array_filter($allMessages, function (MessageInterface $message) use ($targetSubstring) {
            return (
                str_contains(
                    strtolower($message->getTopic()),
                    strtolower($targetSubstring)
                ) === false
            );
        });

        /** @var MockInterface|LoggerInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        /** @var Message $message */
        foreach ($filteredMessages as $message) {
            $logger
                ->shouldReceive('info')
                ->once()
                ->withArgs(
                    [
                        "Message from {$projectName} was published",
                        $message->toArray()[Message::EVENT_DATA],
                    ]
                );
        }

        $decorator = new TopicFilteringMessagesLogger(
            $broker,
            $logger,
            $projectName,
            $targetSubstring
        );

        $actualResponse = $decorator->publish($collection);
        $this->assertEquals($expectedResponse, $actualResponse);
    }


    public function testCanHandleFailedMessages(): void
    {
        $messages         = static::createMessages(3);
        $collection       = GroupedMessagesCollection::createFromMessages(
            ...$messages
        );

        $cause = 'Testing cause';
        $expectedResponse = BrokingBatchResponse::createForMessagesWithBatchStatus(
            false,
            $cause,
            ...$messages
        );

        /** @var MessageBrokerInterface|MockInterface $broker */
        $broker = Mockery::mock(MessageBrokerInterface::class);
        $broker
            ->shouldReceive('publish')
            ->once()
            ->withArgs(
                [
                    $collection,
                ]
            )->andReturn(
                $expectedResponse
            );

        $projectName = 'projectName';

        /** @var MockInterface|LoggerInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        foreach ($expectedResponse->getDispatchedMessages() as $dispatchedMessage) {
            $messageAttributes = $dispatchedMessage->getEventAttributes();
            $logger
                ->shouldReceive('error')
                ->once()
                ->withArgs(
                    [
                        "Error while publishing messages in {$projectName}. Message: Resource - [{$messageAttributes[AbstractMessage::EVENT_TYPE]}], ID - [{$messageAttributes[AbstractMessage::EVENT_OBJECT_ID]}]. Cause: [{$cause}]",
                    ]
                );
        }

        $decorator = new TopicFilteringMessagesLogger(
            $broker,
            $logger,
            $projectName,
            'topic1'
        );

        $actualResponse = $decorator->publish($collection);
        $this->assertEquals($expectedResponse, $actualResponse);
    }
}