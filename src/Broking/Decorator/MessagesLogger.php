<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Decorator;

use Profesia\MessagingCore\Broking\Dto\BrokingBatchResponse;
use Profesia\MessagingCore\Broking\Dto\Message;
use Profesia\MessagingCore\Broking\Dto\MessageCollection;
use Profesia\MessagingCore\Broking\Exception\AbstractMessageBrokerException;
use Profesia\MessagingCore\Broking\MessageBrokerInterface;
use Psr\Log\LoggerInterface;

final class MessagesLogger implements MessageBrokerInterface
{
    private MessageBrokerInterface $decoratedBroker;
    private LoggerInterface $logger;
    private string $projectName;

    public function __construct(MessageBrokerInterface $decoratedBroker, LoggerInterface $logger, string $projectName)
    {
        $this->decoratedBroker = $decoratedBroker;
        $this->logger          = $logger;
        $this->projectName     = $projectName;
    }

    /**
     * @param MessageCollection $collection
     *
     * @return BrokingBatchResponse
     * @throws AbstractMessageBrokerException
     */
    public function publish(MessageCollection $collection): BrokingBatchResponse
    {
        $response           = $this->decoratedBroker->publish($collection);
        $dispatchedMessages = $response->getDispatchedMessages();

        foreach ($dispatchedMessages as $dispatchedMessage) {
            if ($dispatchedMessage->wasDispatchedSuccessfully() === true) {
                $message = $dispatchedMessage->getMessage();
                $this->logger->info(
                    "Event from {$this->projectName} was published",
                    (array)json_decode($message->toArray()[Message::EVENT_DATA], true)
                );

                continue;
            }

            $this->logger->error(
                "Error while publishing messages in {$this->projectName}. Cause: [{$dispatchedMessage->getDispatchReason()}]"
            );
        }

        return $response;
    }
}
