<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Test\Unit\Broking\Decorator;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Profesia\MessagingCore\Broking\Decorator\MessagesLogger;
use Profesia\MessagingCore\Broking\Decorator\TargetFilteringMessagesLogger;
use Profesia\MessagingCore\Broking\Dto\BrokingBatchResponse;
use Profesia\MessagingCore\Broking\Dto\Message;
use Profesia\MessagingCore\Broking\Dto\MessageCollection;
use Profesia\MessagingCore\Broking\MessageBrokerInterface;
use Profesia\MessagingCore\Test\Assets\Helper;
use Psr\Log\LoggerInterface;

class TargetFilteringMessagesLoggerTest extends MockeryTestCase
{
    use Helper;

    public function provideDataFroFiltering(): array
    {
        return [
            [
                static::createMessages(11),
                'target1',
            ],
            [
                static::createMessages(11),
                'TARGET1',
            ],
            [
                static::createMessages(5),
                'not-matching',
            ],
            [
                static::createMessages(11),
                'target1'
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
        $collection       = MessageCollection::createFromMessagesAndChannel(
            'channel',
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
            $messageArray = $message->toArray();

            $target = $messageArray[Message::EVENT_ATTRIBUTES][Message::EVENT_TARGET];

            return (
                str_contains(
                    strtolower($target),
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

        $decorator = new TargetFilteringMessagesLogger(
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
        $collection       = MessageCollection::createFromMessagesAndChannel(
            'channel',
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

        $decorator = new TargetFilteringMessagesLogger(
            $broker,
            $logger,
            $projectName,
            'target1'
        );

        $actualResponse = $decorator->publish($collection);
        $this->assertEquals($expectedResponse, $actualResponse);
    }
}