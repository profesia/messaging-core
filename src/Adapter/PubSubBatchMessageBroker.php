<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Adapter;

use Google\Cloud\Core\Exception\GoogleException;
use Profesia\MessagingCore\Broking\Dto\MessageCollection;
use Profesia\MessagingCore\Broking\Dto\BrokingBatchResponse;
use Profesia\MessagingCore\Broking\Exception\MessageBrokerRuntimeException;

final class PubSubBatchMessageBroker extends AbstractPubSubMessageBroker
{
    public function publish(MessageCollection $collection): BrokingBatchResponse
    {
        try {
            $topic = $this->getTopic($collection->getChannel());
        } catch (MessageBrokerRuntimeException $e) {
            return BrokingBatchResponse::createForMessagesWithBatchStatus(
                   false,
                   $e->getMessage(),
                ...$collection->getMessages()
            );
        }

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
