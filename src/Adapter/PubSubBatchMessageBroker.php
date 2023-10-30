<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Adapter;

use Google\Cloud\Core\Exception\GoogleException;
use Google\Cloud\PubSub\PubSubClient;
use Profesia\MessagingCore\Broking\Dto\BrokingStatus;
use Profesia\MessagingCore\Broking\Dto\DispatchedMessage;
use Profesia\MessagingCore\Broking\Dto\GroupedMessagesCollection;
use Profesia\MessagingCore\Broking\Dto\BrokingBatchResponse;
use Profesia\MessagingCore\Broking\MessageBrokerInterface;

final class PubSubBatchMessageBroker implements MessageBrokerInterface
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
            $topic = $this->pubSubClient->topic($topicName);

            try {
                $topic->publishBatch(
                    $collection->getMessagesDataForTopic($topicName)
                );


                return BrokingBatchResponse::createForMessagesWithBatchStatus(
                    true,
                    null,
                    ...$collection->getMessagesForTopic($topicName)
                );
            } catch (GoogleException $e) {
                return BrokingBatchResponse::createForMessagesWithBatchStatus(
                    false,
                    $e->getMessage(),
                    ...$collection->getMessagesForTopic($topicName)
                );
            }
        }

        return BrokingBatchResponse::createForMessagesWithIndividualStatus(
            ...$dispatchedMessages
        );
    }
}
