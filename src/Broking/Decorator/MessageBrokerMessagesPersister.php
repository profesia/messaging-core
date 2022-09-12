<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Decorator;

use Profesia\MessagingCore\Broking\Dto\BrokingBatchResponse;
use Profesia\MessagingCore\Broking\Dto\MessageCollection;
use Profesia\MessagingCore\Broking\Exception\AbstractMessageBrokerException;
use Profesia\MessagingCore\Broking\MessageBrokerInterface;
use Profesia\MessagingCore\Persistence\EventRepositoryInterface;

class MessageBrokerMessagesPersister implements MessageBrokerInterface
{
    private MessageBrokerInterface $decoratedBroker;
    private EventRepositoryInterface $repository;
    
    public function __construct(MessageBrokerInterface $decoratedBroker, EventRepositoryInterface $repository)
    {
        $this->decoratedBroker = $decoratedBroker;
    }

    /**
     * @param MessageCollection $collection
     *
     * @return BrokingBatchResponse
     * @throws AbstractMessageBrokerException
     */
    public function publish(MessageCollection $collection): BrokingBatchResponse
    {
        $response =  $this->decoratedBroker->publish($collection);
        $this->repository->persistBatch(
            ...$response->getDispatchedMessages()
        );

        return $response;
    }
}
