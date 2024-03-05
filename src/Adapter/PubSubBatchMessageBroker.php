<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Adapter;

use Google\Cloud\Core\Exception\GoogleException;
use Google\Cloud\PubSub\PubSubClient;
use Profesia\MessagingCore\Broking\Dto\Sending\BrokingBatchResponse;
use Profesia\MessagingCore\Broking\Dto\Sending\BrokingStatus;
use Profesia\MessagingCore\Broking\Dto\Sending\DispatchedMessage;
use Profesia\MessagingCore\Broking\Dto\Sending\GroupedMessagesCollection;
use Profesia\MessagingCore\Broking\Dto\Sending\MessageInterface;
use Profesia\MessagingCore\Broking\MessageBrokerInterface;
use Profesia\MessagingCore\Exception\AbstractRuntimeException;

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
            foreach ($collection->getMessagesForTopic($topicName) as $key => $message) {
                try {
                    $encodedMessages[$key]     = $message;
                    $messagesDataInTopic[$key] = $message->encode();
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
            } catch (GoogleException $e) {
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
