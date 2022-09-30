<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Adapter;

use Google\Cloud\Core\Exception\GoogleException;
use Google\Cloud\PubSub\PubSubClient;
use Profesia\MessagingCore\Broking\Dto\MessageCollection;
use Profesia\MessagingCore\Broking\Dto\BrokingBatchResponse;
use Profesia\MessagingCore\Broking\MessageBrokerInterface;

final class PubSubBatchMessageBroker implements MessageBrokerInterface
{
    public function __construct(
        private PubSubClient $pubSubClient
    ) {
    }

    public function publish(MessageCollection $collection): BrokingBatchResponse
    {
        $topic = $this->pubSubClient->topic($collection->getChannel());
        try {
            $topic->publishBatch(
                $collection->getMessagesData()
            );

            return BrokingBatchResponse::createForMessagesWithBatchStatus(
                   true,
                   null,
                ...$collection->getMessages()
            );
        } catch (GoogleException $e) {
            return BrokingBatchResponse::createForMessagesWithBatchStatus(
                   false,
                   $e->getMessage(),
                ...$collection->getMessages()
            );
        }
    }
}
