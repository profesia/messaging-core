<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Adapter;

use Google\Cloud\Core\Exception\GoogleException;
use Profesia\MessagingCore\Broking\Dto\DispatchedMessage;
use Profesia\MessagingCore\Broking\Dto\MessageCollection;
use Profesia\MessagingCore\Broking\Dto\BrokingStatus;
use Profesia\MessagingCore\Broking\Dto\BrokingBatchResponse;
use Profesia\MessagingCore\Broking\Exception\MessageBrokerRuntimeException;

class PubSubMessageBroker extends AbstractPubSubMessageBroker
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

        $dispatchedMessages = [];
        foreach ($collection->getMessages() as $key => $message) {
            try {
                $topic->publish($message->toArray());
                $dispatchedMessages[$key] = new DispatchedMessage(
                    $message,
                    new BrokingStatus(true)
                );
            } catch (GoogleException $e) {
                $dispatchedMessages[$key] = new DispatchedMessage(
                    $message,
                    new BrokingStatus(false, $e->getMessage())
                );
            }
        }

        return BrokingBatchResponse::createForMessagesWithIndividualStatus(
            ...$dispatchedMessages
        );
    }
}
