<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Test\Unit\Broking\Decorator;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Profesia\MessagingCore\Broking\Decorator\AllMessagesPersister;
use Profesia\MessagingCore\Broking\Dto\BrokingBatchResponse;
use Profesia\MessagingCore\Broking\Dto\GroupedMessagesCollection;
use Profesia\MessagingCore\Broking\MessageBrokerInterface;
use Profesia\MessagingCore\Persistence\DispatchedEventRepositoryInterface;
use Profesia\MessagingCore\Test\Assets\Helper;

class AllMessagesPersisterTest extends MockeryTestCase
{
    use Helper;

    public function testCanPublish(): void
    {
        $messages         = static::createMessages(3);
        $collection       = GroupedMessagesCollection::createFromMessagesAndChannel(
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

        /** @var DispatchedEventRepositoryInterface|MockInterface $repository */
        $repository = Mockery::mock(DispatchedEventRepositoryInterface::class);
        $repository
            ->shouldReceive('persistBatch')
            ->once()
            ->withArgs(
                [
                    ...$expectedResponse->getDispatchedMessages(),
                ]
            );

        $decorator = new AllMessagesPersister(
            $broker,
            $repository
        );

        $actualResponse = $decorator->publish($collection);
        $this->assertEquals($expectedResponse, $actualResponse);
    }
}
