<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Adapter;

use Aws\EventBridge\EventBridgeClient;
use Aws\Exception\AwsException;
use Profesia\MessagingCore\Broking\Dto\Sending\BrokingBatchResponse;
use Profesia\MessagingCore\Broking\Dto\Sending\BrokingStatus;
use Profesia\MessagingCore\Broking\Dto\Sending\DispatchedMessage;
use Profesia\MessagingCore\Broking\Dto\Sending\GroupedMessagesCollection;
use Profesia\MessagingCore\Broking\Dto\Sending\MessageInterface;
use Profesia\MessagingCore\Broking\MessageBrokerInterface;
use Profesia\MessagingCore\Exception\AbstractRuntimeException;

final class AwsBatchMessageBroker implements MessageBrokerInterface
{
    public function __construct(
        private readonly EventBridgeClient $eventBridgeClient,
    )
    {
    }

    public function publish(GroupedMessagesCollection $collection): BrokingBatchResponse
    {
        $brokingBatchResponse = BrokingBatchResponse::createEmpty();
        foreach ($collection->getTopics() as $topicName) {
            $messages           = $collection->getMessagesForTopic($topicName);
            $entries            = [];
            $encodedMessages    = [];
            $dispatchedMessages = [];
            $arrayOrderedKeys   = array_keys($messages);

            foreach ($messages as $key => $message) {
                try {
                    $entries[$key]         = [...$message->encode(), 'EventBusName' => $topicName];
                    $encodedMessages[$key] = $message;
                } catch (AbstractRuntimeException $e) {
                    $dispatchedMessages[$key] = new DispatchedMessage(
                        $message,
                        new BrokingStatus(false, $e->getMessage())
                    );
                }
            }

            try {
                $this->eventBridgeClient->putEvents(['Entries' => $entries]);

                $dispatchedBatch = [];
                foreach ($arrayOrderedKeys as $key) {
                    if (array_key_exists($key, $encodedMessages)) {
                        $dispatchedBatch[$key] = new DispatchedMessage(
                            $encodedMessages[$key],
                            new BrokingStatus(
                                true
                            )
                        );
                    } else {
                        $dispatchedBatch[$key] = $dispatchedMessages[$key];
                    }
                }

                $brokingBatchResponse = $brokingBatchResponse->appendDispatchedMessages(
                    ...$dispatchedBatch
                );

            } catch (AwsException $e) {
                $dispatchedBatch = [];
                foreach ($arrayOrderedKeys as $key) {
                    if (array_key_exists($key, $encodedMessages)) {
                        $dispatchedBatch[$key] = new DispatchedMessage(
                            $encodedMessages[$key],
                            new BrokingStatus(
                                false,
                                $e->getMessage()
                            )
                        );
                    } else {
                        $dispatchedBatch[$key] = $dispatchedMessages[$key];
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
