<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Adapter;

use Google\Cloud\PubSub\PubSubClient;
use Google\Cloud\PubSub\Topic;
use Profesia\MessagingCore\Broking\Dto\MessageCollection;
use Profesia\MessagingCore\Broking\MessageBrokerInterface;
use RuntimeException;

final class PubSubMessageBroker implements MessageBrokerInterface
{
    private PubSubClient $pubSubClient;

    public function __construct(PubSubClient $pubSubClient)
    {
        $this->pubSubClient = $pubSubClient;
    }

    public function publish(MessageCollection $collection): void
    {
        $topic = $this->getTopic($collection->getChannel());

        $topic->publishBatch(
            $collection->getMessagesData()
        );
    }

    private function getTopic(string $topicName): Topic
    {
        $topic = $this->pubSubClient->topic($topicName);

        if ($topic->exists() === false) {
            throw new RuntimeException("Topic with name: [{$topicName}] does not exist");
        }

        return $topic;

    }
}
