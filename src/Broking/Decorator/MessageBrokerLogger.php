<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Decorator;

use Profesia\MessagingCore\Broking\Dto\Message;
use Profesia\MessagingCore\Broking\Dto\MessageCollection;
use Profesia\MessagingCore\Broking\Dto\BrokingBatchResponse;
use Profesia\MessagingCore\Broking\MessageBrokerInterface;
use Psr\Log\LoggerInterface;

final class MessageBrokerLogger implements MessageBrokerInterface
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


    public function publish(MessageCollection $collection): BrokingBatchResponse
    {
        $response = $this->decoratedBroker->publish($collection);
        $statuses = $response->getMessageStatuses();

        foreach ($statuses as $key => $status) {
            if ($status->isSuccessful() === true) {
                $messageData = $collection->getMessageData($key);
                $this->logger->info(
                    "Event from {$this->projectName} was published",
                    json_decode($messageData[Message::EVENT_DATA], true)
                );

                continue;
            }

            $this->logger->error(
                "Error while publishing messages in {$this->projectName}. Cause: [{$status->getReason()}]"
            );
        }

        return $response;
    }
}
