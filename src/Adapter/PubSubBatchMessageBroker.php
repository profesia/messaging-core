<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Adapter;

use Google\Cloud\Core\Exception\GoogleException;
use Google\Cloud\PubSub\PubSubClient;
use Profesia\MessagingCore\Broking\Dto\BrokingStatus;
use Profesia\MessagingCore\Broking\Dto\DispatchedMessage;
use Profesia\MessagingCore\Broking\Dto\GroupedMessagesCollection;
use Profesia\MessagingCore\Broking\Dto\BrokingBatchResponse;
use Profesia\MessagingCore\Broking\Dto\Message;
use Profesia\MessagingCore\Broking\MessageBrokerInterface;
use Profesia\MessagingCore\Exception\AbstractRuntimeException;

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
        $brokingBatchResponse = BrokingBatchResponse::createEmpty();
        foreach ($collection->getTopics() as $topicName) {
            $topic = $this->pubSubClient->topic($topicName);

            $messagesDataInTopic = [];
            $dispatchedMessages  = [];
            $encodedMessages     = [];
            foreach ($collection->getMessagesForTopic($topicName) as $key => $message) {
                try {
                    $encodedMessages[$key]     = $message;
                    $messagesDataInTopic[$key] = $message->toArray();
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
                        array_map(static function (Message $message): DispatchedMessage {
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
                        array_map(static function (Message $message) use ($e): DispatchedMessage {
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
