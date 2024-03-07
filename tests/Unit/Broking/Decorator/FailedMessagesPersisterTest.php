<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Test\Unit\Broking\Decorator;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Profesia\MessagingCore\Broking\Decorator\FailedMessagesPersister;
use Profesia\MessagingCore\Broking\Dto\Sending\BrokingBatchResponse;
use Profesia\MessagingCore\Broking\Dto\Sending\BrokingStatus;
use Profesia\MessagingCore\Broking\Dto\Sending\DispatchedMessage;
use Profesia\MessagingCore\Broking\Dto\Sending\GroupedMessagesCollection;
use Profesia\MessagingCore\Broking\MessageBrokerInterface;
use Profesia\MessagingCore\Persistence\DispatchedEventRepositoryInterface;
use Profesia\MessagingCore\Test\Assets\Helper;

class FailedMessagesPersisterTest extends MockeryTestCase
{
    use Helper;

    public function testCanPublish(): void
    {
        $messages         = static::createPubSubMessages(5);
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

        $failedMessages = array_filter(
            $expectedResponse->getDispatchedMessages(),
            function (DispatchedMessage $dispatchedMessage): bool
            {
                return ($dispatchedMessage->wasDispatchedSuccessfully() === false);
            }
        );

        /** @var DispatchedEventRepositoryInterface|MockInterface $repository */
        $repository = Mockery::mock(DispatchedEventRepositoryInterface::class);
        $repository
            ->shouldReceive('persistBatch')
            ->once()
            ->withArgs(
                [
                    ...$failedMessages,
                ]
            );

        $decorator = new FailedMessagesPersister(
            $broker,
            $repository
        );

        $actualResponse = $decorator->publish($collection);
        $this->assertEquals($expectedResponse, $actualResponse);
    }
}
