<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Decorator;

use Profesia\MessagingCore\Broking\Dto\Message;
use Profesia\MessagingCore\Broking\Dto\MessageCollection;
use Profesia\MessagingCore\Broking\Exception\AbstractMessageBrokerException;
use Profesia\MessagingCore\Broking\MessageBrokerInterface;
use Psr\Log\LoggerInterface;

final class MessageBrokerLogger implements MessageBrokerInterface
{
    private MessageBrokerInterface $decoratedBroker;
    private LoggerInterface $logger;

    public function __construct(MessageBrokerInterface $decoratedBroker, LoggerInterface $logger)
    {
        $this->decoratedBroker = $decoratedBroker;
        $this->logger          = $logger;
    }


    public function publish(MessageCollection $collection): void
    {
        try {
            $this->decoratedBroker->publish($collection);

            $messagesData = $collection->getMessagesData();
            foreach ($messagesData as $messageData) {
                $this->logger->info(
                    'Event from Profesia Message Broker preprocessing',
                    json_decode($messageData[Message::EVENT_DATA], true)
                );
            }
        } catch (AbstractMessageBrokerException $e) {
            $this->logger->error(
                "Error while publishing messages in Profesia Message Broker. Cause: [{$e->getMessage()}]"
            );
        }
    }
}
