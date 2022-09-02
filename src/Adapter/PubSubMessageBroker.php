<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Adapter;

use Google\Cloud\PubSub\PubSubClient;
use Google\Cloud\PubSub\Topic;
use Profesia\MessagingCore\Broking\Dto\MessageCollection;
use Profesia\MessagingCore\Broking\Exception\AbstractMessageBrokerException;
use Profesia\MessagingCore\Broking\Exception\MessageBrokerRuntimeException;
use Profesia\MessagingCore\Broking\MessageBrokerInterface;

final class PubSubMessageBroker implements MessageBrokerInterface
{
    private PubSubClient $pubSubClient;

    public function __construct(PubSubClient $pubSubClient)
    {
        $this->pubSubClient = $pubSubClient;
    }

    /**
     * @param MessageCollection $collection
     *
     * @return void
     * @throws AbstractMessageBrokerException
     */
    public function publish(MessageCollection $collection): void
    {
        $topic = $this->getTopic($collection->getChannel());

        $topic->publishBatch(
            $collection->getMessagesData()
        );
    }

    /**
     * @param string $topicName
     *
     * @return Topic
     * @throws MessageBrokerRuntimeException
     */
    private function getTopic(string $topicName): Topic
    {
        $topic = $this->pubSubClient->topic($topicName);

        if ($topic->exists() === false) {
            throw new MessageBrokerRuntimeException("Topic with name: [{$topicName}] does not exist");
        }

        return $topic;

    }
}
