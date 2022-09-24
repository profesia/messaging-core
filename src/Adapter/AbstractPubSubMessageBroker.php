<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Adapter;

use Google\Cloud\PubSub\PubSubClient;
use Google\Cloud\PubSub\Topic;
use Profesia\MessagingCore\Broking\Exception\MessageBrokerRuntimeException;
use Profesia\MessagingCore\Broking\MessageBrokerInterface;

abstract class AbstractPubSubMessageBroker implements MessageBrokerInterface
{
    public function __construct(
        private PubSubClient $pubSubClient)
    {
    }

    /**
     * @param string $topicName
     *
     * @return Topic
     * @throws MessageBrokerRuntimeException
     */
    protected function getTopic(string $topicName): Topic
    {
        $topic = $this->pubSubClient->topic($topicName);

        if ($topic->exists() === false) {
            throw new MessageBrokerRuntimeException("Topic with name: [{$topicName}] does not exist");
        }

        return $topic;

    }
}
