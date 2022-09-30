<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Adapter;

use Google\Cloud\Core\Exception\GoogleException;
use Google\Cloud\PubSub\PubSubClient;
use Profesia\MessagingCore\Broking\Dto\DispatchedMessage;
use Profesia\MessagingCore\Broking\Dto\MessageCollection;
use Profesia\MessagingCore\Broking\Dto\BrokingStatus;
use Profesia\MessagingCore\Broking\Dto\BrokingBatchResponse;
use Profesia\MessagingCore\Broking\MessageBrokerInterface;

final class PubSubMessageBroker implements MessageBrokerInterface
{
    private PubSubClient $pubSubClient;

    public function __construct(PubSubClient $pubSubClient)
    {
        $this->pubSubClient = $pubSubClient;
    }

    public function publish(MessageCollection $collection): BrokingBatchResponse
    {
        $topic              = $this->pubSubClient->topic($collection->getChannel());
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
                    new BrokingStatus(
                        false,
                        $e->getMessage()
                    )
                );
            }
        }

        return BrokingBatchResponse::createForMessagesWithIndividualStatus(
            ...$dispatchedMessages
        );
    }
}
