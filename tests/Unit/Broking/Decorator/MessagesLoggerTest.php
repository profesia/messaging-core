<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Unit\Broking\Decorator;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Profesia\MessagingCore\Broking\Decorator\MessagesLogger;
use Profesia\MessagingCore\Broking\Dto\BrokingBatchResponse;
use Profesia\MessagingCore\Broking\Dto\BrokingStatus;
use Profesia\MessagingCore\Broking\Dto\DispatchedMessage;
use Profesia\MessagingCore\Broking\Dto\Message;
use Profesia\MessagingCore\Broking\Dto\MessageCollection;
use Profesia\MessagingCore\Broking\MessageBrokerInterface;
use Profesia\MessagingCore\Test\Unit\Helper;
use Psr\Log\LoggerInterface;

class MessagesLoggerTest extends MockeryTestCase
{
    use Helper;

    public function testCanHandleSuccessfulMessages(): void
    {
        $messages         = static::createMessages(3);
        $collection       = MessageCollection::createFromMessagesAndChannel(
               'channel',
            ...$messages
        );
        $expectedResponse = BrokingBatchResponse::createForMessagesWithBatchStatus(
               true,
               null,
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
        foreach ($expectedResponse->getDispatchedMessages() as $message) {
            $logger
                ->shouldReceive('info')
                ->once()
                ->withArgs(
                    [
                        "Message from {$projectName} was published",
                        (array)json_decode($message->getMessage()->toArray()[Message::EVENT_DATA], true),
                    ]
                );
        }

        $decorator = new MessagesLogger(
            $broker,
            $logger,
            $projectName
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

        $decorator = new MessagesLogger(
            $broker,
            $logger,
            $projectName
        );

        $actualResponse = $decorator->publish($collection);
        $this->assertEquals($expectedResponse, $actualResponse);
    }

    public function testCanHandleMixedMessages(): void
    {
        $messages         = static::createMessages(5);
        $collection       = MessageCollection::createFromMessagesAndChannel(
               'channel',
            ...$messages
        );

        $index = 1;
        $dispatchedMessages = [];
        foreach ($messages as $message) {
            $dispatchedMessages[] = new DispatchedMessage(
                $message,
                new BrokingStatus(
                    $index % 2 !== 0,
                    $index % 2 !== 0 ? null: "Reason{$index}"
                )
            );

            $index++;
        }

        $expectedResponse = BrokingBatchResponse::createForMessagesWithIndividualStatus(...$dispatchedMessages);

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
            if ($dispatchedMessage->wasDispatchedSuccessfully() === true) {
                $logger
                    ->shouldReceive('info')
                    ->once()
                    ->withArgs(
                        [
                            "Message from {$projectName} was published",
                            (array)json_decode($messageData[Message::EVENT_DATA], true),
                        ]
                    );
            } else {
                $logger
                    ->shouldReceive('error')
                    ->once()
                    ->withArgs(
                        [
                            "Error while publishing messages in {$projectName}. Message: Resource - [{$messageAttributes[Message::EVENT_TYPE]}], ID - [{$messageAttributes[Message::EVENT_OBJECT_ID]}]. Cause: [{$dispatchedMessage->getDispatchReason()}]",
                        ]
                    );
            }
        }

        $decorator = new MessagesLogger(
            $broker,
            $logger,
            $projectName
        );

        $actualResponse = $decorator->publish($collection);
        $this->assertEquals($expectedResponse, $actualResponse);
    }
}
