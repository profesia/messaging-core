<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Adapter;

use Google\Cloud\Core\Exception\GoogleException;
use Google\Cloud\PubSub\PubSubClient;
use Profesia\MessagingCore\Broking\Dto\DispatchedMessage;
use Profesia\MessagingCore\Broking\Dto\GroupedMessagesCollection;
use Profesia\MessagingCore\Broking\Dto\BrokingStatus;
use Profesia\MessagingCore\Broking\Dto\BrokingBatchResponse;
use Profesia\MessagingCore\Broking\MessageBrokerInterface;

final class PubSubMessageBroker implements MessageBrokerInterface
{
    private PubSubClient $pubSubClient;

    public function __construct(
        PubSubClient $pubSubClient
    )
    {
        $this->pubSubClient = $pubSubClient;
    }

    public function publish(GroupedMessagesCollection $collection): BrokingBatchResponse
    {
        $dispatchedMessages = [];
        foreach ($collection->getTopics() as $topicName) {
            $topic    = $this->pubSubClient->topic($topicName);
            $messages = $collection->getMessagesForTopic($topicName);

            foreach ($messages as $key => $message) {
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
        }

        return BrokingBatchResponse::createForMessagesWithIndividualStatus(
            ...$dispatchedMessages
        );
    }
}
