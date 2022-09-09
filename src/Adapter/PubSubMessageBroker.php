<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Adapter;

use Google\Cloud\Core\Exception\ServiceException;
use Profesia\MessagingCore\Broking\Dto\MessageCollection;
use Profesia\MessagingCore\Broking\Dto\MessageStatus;
use Profesia\MessagingCore\Broking\Dto\BrokingBatchResponse;

class PubSubMessageBroker extends AbstractPubSubMessageBroker
{
    public function publish(MessageCollection $collection): BrokingBatchResponse
    {
        $topic = $this->getTopic($collection->getChannel());

        $statuses = [];
        foreach ($collection->getMessagesData() as $key => $messageData) {
            try {
                $topic->publish($messageData);
                $statuses[$key] = new MessageStatus(true);
            } catch (ServiceException $e) {
                $statuses[$key] = new MessageStatus(false, $e->getMessage());
            }
        }

        return BrokingBatchResponse::createFromMessageStatuses(
            ...$statuses
        );
    }
}
