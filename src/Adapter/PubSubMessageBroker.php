<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Adapter;

use Google\Cloud\Core\Exception\GoogleException;
use Google\Cloud\PubSub\PubSubClient;
use Profesia\MessagingCore\Broking\Dto\Sending\BrokingBatchResponse;
use Profesia\MessagingCore\Broking\Dto\Sending\BrokingStatus;
use Profesia\MessagingCore\Broking\Dto\Sending\DispatchedMessage;
use Profesia\MessagingCore\Broking\Dto\Sending\GroupedMessagesCollection;
use Profesia\MessagingCore\Broking\MessageBrokerInterface;
use Profesia\MessagingCore\Exception\AbstractRuntimeException;

final class PubSubMessageBroker implements MessageBrokerInterface
{
    public function __construct(
        private readonly PubSubClient $pubSubClient
    )
    {
    }

    public function publish(GroupedMessagesCollection $collection): BrokingBatchResponse
    {
        $dispatchedMessages = [];
        $index = 0;
        foreach ($collection->getTopics() as $topicName) {
            $topic    = $this->pubSubClient->topic($topicName);
            $messages = $collection->getMessagesForTopic($topicName);

            foreach ($messages as $message) {
                try {
                    $topic->publish($message->encode());
                    $dispatchedMessages[$index++] = new DispatchedMessage(
                        $message,
                        new BrokingStatus(true)
                    );
                } catch (GoogleException | AbstractRuntimeException $e) {
                    $dispatchedMessages[$index++] = new DispatchedMessage(
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
