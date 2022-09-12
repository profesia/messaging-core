<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Adapter;

use Google\Cloud\Core\Exception\ServiceException;
use Profesia\MessagingCore\Broking\Dto\MessageCollection;
use Profesia\MessagingCore\Broking\Dto\BrokingBatchResponse;

final class PubSubBatchMessageBroker extends AbstractPubSubMessageBroker
{
    public function publish(MessageCollection $collection): BrokingBatchResponse
    {
        $topic = $this->getTopic($collection->getChannel());
        $messages = $collection->getMessages();

        try {
            $topic->publishBatch(
                $collection->getMessagesData()
            );

            return BrokingBatchResponse::createForMessagesWithBatchStatus(
                true,
                null,
                ...$messages
            );
        } catch (ServiceException $e) {
            return BrokingBatchResponse::createForMessagesWithBatchStatus(
                false,
                $e->getMessage(),
                ...$messages
            );
        }
    }
}