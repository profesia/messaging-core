<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Decorator;

use Profesia\MessagingCore\Broking\Dto\Sending\BrokingBatchResponse;
use Profesia\MessagingCore\Broking\Dto\Sending\GroupedMessagesCollection;
use Profesia\MessagingCore\Broking\MessageBrokerInterface;
use Profesia\MessagingCore\Persistence\DispatchedEventRepositoryInterface;

class AllMessagesPersister implements MessageBrokerInterface
{
    public function __construct(
        private readonly MessageBrokerInterface $decoratedBroker,
        private readonly DispatchedEventRepositoryInterface $repository
    )
    {
    }

    /**
     * @param GroupedMessagesCollection $collection
     *
     * @return BrokingBatchResponse
     */
    public function publish(GroupedMessagesCollection $collection): BrokingBatchResponse
    {
        $response = $this->decoratedBroker->publish($collection);
        $this->repository->persistBatch(
            ...$response->getDispatchedMessages()
        );

        return $response;
    }
}
