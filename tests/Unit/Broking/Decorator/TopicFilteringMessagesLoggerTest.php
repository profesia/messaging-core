<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Test\Unit\Broking\Decorator;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Profesia\MessagingCore\Broking\Decorator\TopicFilteringMessagesLogger;
use Profesia\MessagingCore\Broking\Dto\BrokingBatchResponse;
use Profesia\MessagingCore\Broking\Dto\Message;
use Profesia\MessagingCore\Broking\Dto\GroupedMessagesCollection;
use Profesia\MessagingCore\Broking\MessageBrokerInterface;
use Profesia\MessagingCore\Test\Assets\Helper;
use Psr\Log\LoggerInterface;

class TopicFilteringMessagesLoggerTest extends MockeryTestCase
{
    use Helper;

    public function provideDataFroFiltering(): array
    {
        return [
            [
                static::createMessages(11),
                'topic1',
            ],
            [
                static::createMessages(11),
                'TOPIC2',
            ],
            [
                static::createMessages(5),
                'not-matching',
            ],
            [
                static::createMessages(11),
                'topic1'
            ],
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
        $filteredMessages = array_filter($allMessages, function (Message $message) use ($targetSubstring) {
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
                        (array)json_decode($message->toArray()[Message::EVENT_DATA], true),
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
            $messageData       = $dispatchedMessage->getMessage()->toArray();
            $messageAttributes = $messageData[Message::EVENT_ATTRIBUTES];
            $logger
                ->shouldReceive('error')
                ->once()
                ->withArgs(
                    [
                        "Error while publishing messages in {$projectName}. Message: Resource - [{$messageAttributes[Message::EVENT_TYPE]}], ID - [{$messageAttributes[Message::EVENT_OBJECT_ID]}]. Cause: [{$cause}]",
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