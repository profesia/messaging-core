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

                $brokingBatchResponse = $brokingBatchResponse->appendDispatchedMessages(
                    ...array_replace(
                        array_map(static function (MessageInterface $message): DispatchedMessage {
                            return new DispatchedMessage(
                                $message,
                                new BrokingStatus(
                                    true
                                )
                            );
                        }, $encodedMessages),
                        $dispatchedMessages
                    )
                );
            } catch (AwsException $e) {
                $brokingBatchResponse = $brokingBatchResponse->appendDispatchedMessages(
                    ...array_replace(
                        array_map(static function (MessageInterface $message) use ($e): DispatchedMessage {
                            return new DispatchedMessage(
                                $message,
                                new BrokingStatus(
                                    false,
                                    $e->getMessage()
                                )
                            );
                        }, $encodedMessages),
                        $dispatchedMessages)
                );
            }
        }

        return $brokingBatchResponse;
    }
}
