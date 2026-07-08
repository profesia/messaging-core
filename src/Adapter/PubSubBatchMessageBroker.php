<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Adapter;

use Google\Cloud\Core\Exception\GoogleException;
use Google\Cloud\PubSub\PubSubClient;
use Profesia\MessagingCoreContracts\Broking\Dto\Sending\BrokingBatchResponse;
use Profesia\MessagingCoreContracts\Broking\Dto\Sending\BrokingStatus;
use Profesia\MessagingCoreContracts\Broking\Dto\Sending\DispatchedMessage;
use Profesia\MessagingCoreContracts\Broking\Dto\Sending\GroupedMessagesCollection;
use Profesia\MessagingCoreContracts\Broking\MessageBrokerInterface;
use Profesia\MessagingCoreContracts\Exception\AbstractRuntimeException;

final class PubSubBatchMessageBroker implements MessageBrokerInterface
{
    public function __construct(
        private readonly PubSubClient $pubSubClient
    )
    {
    }

    public function publish(GroupedMessagesCollection $collection): BrokingBatchResponse
    {
        $brokingBatchResponse = BrokingBatchResponse::createEmpty();
        foreach ($collection->getTopics() as $topicName) {
            $topic = $this->pubSubClient->topic($topicName);

            $messagesDataInTopic = [];
            $dispatchedMessages  = [];
            $encodedMessages     = [];
            $messages            = $collection->getMessagesForTopic($topicName);
            $arrayOrderedKeys    = array_keys($messages);
            foreach ($messages as $key => $message) {
                try {
                    $messagesDataInTopic[$key] = $message->encode();
                    $encodedMessages[$key]     = $message;
                } catch (AbstractRuntimeException $e) {
                    $dispatchedMessages[$key] = new DispatchedMessage(
                        $message,
                        new BrokingStatus(
                            false,
                            $e->getMessage()
                        )
                    );
                }
            }

            try {
                $topic->publishBatch(
                    $messagesDataInTopic
                );

                $dispatchedBatch = [];
                foreach ($arrayOrderedKeys as $key) {
                    if (array_key_exists($key, $encodedMessages)) {
                        $dispatchedBatch[] = new DispatchedMessage(
                            $encodedMessages[$key],
                            new BrokingStatus(
                                true
                            )
                        );
                    } else {
                        $dispatchedBatch[] = $dispatchedMessages[$key];
                    }
                }

                $brokingBatchResponse = $brokingBatchResponse->appendDispatchedMessages(
                    ...$dispatchedBatch
                );

            } catch (GoogleException $e) {
                $dispatchedBatch = [];
                foreach ($arrayOrderedKeys as $key) {
                    if (array_key_exists($key, $encodedMessages)) {
                        $dispatchedBatch[] = new DispatchedMessage(
                            $encodedMessages[$key],
                            new BrokingStatus(
                                false,
                                $e->getMessage()
                            )
                        );
                    } else {
                        $dispatchedBatch[] = $dispatchedMessages[$key];
                    }
                }

                $brokingBatchResponse = $brokingBatchResponse->appendDispatchedMessages(
                    ...$dispatchedBatch
                );
            }
        }

        return $brokingBatchResponse;
    }
}
