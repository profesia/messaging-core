<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Adapter;

use Aws\EventBridge\EventBridgeClient;
use Aws\Exception\AwsException;
use Profesia\MessagingCore\Broking\Dto\Sending\BrokingBatchResponse;
use Profesia\MessagingCore\Broking\Dto\Sending\BrokingStatus;
use Profesia\MessagingCore\Broking\Dto\Sending\DispatchedMessage;
use Profesia\MessagingCore\Broking\Dto\Sending\GroupedMessagesCollection;
use Profesia\MessagingCore\Broking\MessageBrokerInterface;
use Profesia\MessagingCore\Exception\AbstractRuntimeException;

final class AwsMessageBroker implements MessageBrokerInterface
{
    public function __construct(
        private readonly EventBridgeClient $eventBridgeClient,
    ) {
    }

    public function publish(GroupedMessagesCollection $collection): BrokingBatchResponse
    {
        $dispatchedMessages = [];

        foreach ($collection->getTopics() as $topicName) {
            $messages        = $collection->getMessagesForTopic($topicName);
            $entries         = [];
            $encodedMessages = [];

            foreach ($messages as $message) {
                try {
                    $entries[]         = [...$message->encode(), 'EventBusName' => $topicName];
                    $encodedMessages[] = $message;
                } catch (AbstractRuntimeException $e) {
                    $dispatchedMessages[] = new DispatchedMessage(
                        $message,
                        new BrokingStatus(false, $e->getMessage())
                    );
                }
            }

            try {
                $this->eventBridgeClient->putEvents(['Entries' => $entries]);

                foreach ($encodedMessages as $successfulMessage) {
                    $dispatchedMessages[] = new DispatchedMessage(
                        $successfulMessage,
                        new BrokingStatus(true)
                    );
                }
            } catch (AwsException $e) {
                foreach ($encodedMessages as $unsuccessfulMessage) {
                    $dispatchedMessages[] = new DispatchedMessage(
                        $unsuccessfulMessage,
                        new BrokingStatus(false, $e->getMessage())
                    );
                }
            }
        }

        return BrokingBatchResponse::createForMessagesWithIndividualStatus(...$dispatchedMessages);
    }
}
