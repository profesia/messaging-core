<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Decorator;

use Profesia\MessagingCore\Broking\Dto\BrokingBatchResponse;
use Profesia\MessagingCore\Broking\Dto\DispatchedMessage;
use Profesia\MessagingCore\Broking\Dto\MessageCollection;
use Profesia\MessagingCore\Broking\MessageBrokerInterface;
use Profesia\MessagingCore\Persistence\DispatchedEventRepositoryInterface;

class FailedMessagesPersister implements MessageBrokerInterface
{
    private MessageBrokerInterface $decoratedBroker;
    private DispatchedEventRepositoryInterface $repository;

    public function __construct(
        MessageBrokerInterface $decoratedBroker,
        DispatchedEventRepositoryInterface $repository
    ) {
        $this->decoratedBroker = $decoratedBroker;
        $this->repository      = $repository;
    }

    public function publish(MessageCollection $collection): BrokingBatchResponse
    {
        $response = $this->decoratedBroker->publish($collection);
        $failedMessages = array_filter(
            $response->getDispatchedMessages(),
            function (DispatchedMessage $dispatchedMessage): bool {
                return ($dispatchedMessage->wasDispatchedSuccessfully() === false);
            }
        );
        $this->repository->persistBatch(
            ...$failedMessages
        );

        return $response;
    }
}
