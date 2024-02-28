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

final class AWSEventBridgeMessageBroker implements MessageBrokerInterface
{
    private EventBridgeClient $eventBridgeClient;

    public function __construct(
        EventBridgeClient $eventBridgeClient
    ) {
        $this->eventBridgeClient = $eventBridgeClient;
    }

    public function publish(GroupedMessagesCollection $collection): BrokingBatchResponse
    {
        $dispatchedMessages = [];

        foreach ($collection->getTopics() as $topicName) {
            $messages = $collection->getMessagesForTopic($topicName);

            $entries = array_map(function (MessageInterface $message) use ($topicName) {
                return [
                    ...$message->encode(), //todo exception
                    'EventBusName' => $topicName,
                ];
            }, $messages);

            try {
                $this->eventBridgeClient->putEvents(['Entries' => $entries]);

                foreach ($messages as $message) {
                    $dispatchedMessages[] = new DispatchedMessage(
                        $message,
                        new BrokingStatus(true)
                    );
                }
            } catch (AwsException $e) {
                foreach ($messages as $message) {
                    $dispatchedMessages[] = new DispatchedMessage(
                        $message,
                        new BrokingStatus(false, $e->getMessage())
                    );
                }
            }

        }

        return BrokingBatchResponse::createForMessagesWithIndividualStatus(...$dispatchedMessages);
    }
}
