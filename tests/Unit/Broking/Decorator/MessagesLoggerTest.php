<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Test\Unit\Broking\Decorator;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Profesia\MessagingCore\Broking\Decorator\MessagesLogger;
use Profesia\MessagingCore\Broking\Dto\Sending\BrokingBatchResponse;
use Profesia\MessagingCore\Broking\Dto\Sending\BrokingStatus;
use Profesia\MessagingCore\Broking\Dto\Sending\DispatchedMessage;
use Profesia\MessagingCore\Broking\Dto\Sending\GroupedMessagesCollection;
use Profesia\MessagingCore\Broking\Dto\Sending\PubSubMessage;
use Profesia\MessagingCore\Broking\MessageBrokerInterface;
use Profesia\MessagingCore\Test\Assets\Helper;
use Psr\Log\LoggerInterface;

class MessagesLoggerTest extends MockeryTestCase
{
    use Helper;

    public function testCanHandleSuccessfulMessages(): void
    {
        $messages         = static::createMessages(3);
        $collection       = GroupedMessagesCollection::createFromMessages(
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
                        $message->getEventData(),
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
                        "Error while publishing messages in {$projectName}. Message: Resource - [{$messageAttributes[PubSubMessage::EVENT_TYPE]}], ID - [{$messageAttributes[PubSubMessage::EVENT_OBJECT_ID]}]. Cause: [{$cause}]",
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
        $collection       = GroupedMessagesCollection::createFromMessages(
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
            $messageAttributes = $dispatchedMessage->getEventAttributes();
            if ($dispatchedMessage->wasDispatchedSuccessfully() === true) {
                $logger
                    ->shouldReceive('info')
                    ->once()
                    ->withArgs(
                        [
                            "Message from {$projectName} was published",
                            $dispatchedMessage->getEventData(),
                        ]
                    );
            } else {
                $logger
                    ->shouldReceive('error')
                    ->once()
                    ->withArgs(
                        [
                            "Error while publishing messages in {$projectName}. Message: Resource - [{$messageAttributes[PubSubMessage::EVENT_TYPE]}], ID - [{$messageAttributes[PubSubMessage::EVENT_OBJECT_ID]}]. Cause: [{$dispatchedMessage->getDispatchReason()}]",
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
