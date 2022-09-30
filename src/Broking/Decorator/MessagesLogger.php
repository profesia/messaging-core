<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Decorator;

use Profesia\MessagingCore\Broking\Dto\BrokingBatchResponse;
use Profesia\MessagingCore\Broking\Dto\Message;
use Profesia\MessagingCore\Broking\Dto\MessageCollection;
use Profesia\MessagingCore\Broking\MessageBrokerInterface;
use Psr\Log\LoggerInterface;

final class MessagesLogger implements MessageBrokerInterface
{
    public function __construct(
        private MessageBrokerInterface $decoratedBroker,
        private LoggerInterface $logger,
        private string $projectName)
    {
    }

    /**
     * @param MessageCollection $collection
     *
     * @return BrokingBatchResponse
     */
    public function publish(MessageCollection $collection): BrokingBatchResponse
    {
        $response           = $this->decoratedBroker->publish($collection);
        $dispatchedMessages = $response->getDispatchedMessages();

        foreach ($dispatchedMessages as $dispatchedMessage) {
            $messageData = $dispatchedMessage->getMessage()->toArray();
            if ($dispatchedMessage->wasDispatchedSuccessfully() === true) {
                $this->logger->info(
                    "Message from {$this->projectName} was published",
                    (array)json_decode($messageData[Message::EVENT_DATA], true)
                );

                continue;
            }

            $messageAttributes = $messageData[Message::EVENT_ATTRIBUTES];
            $this->logger->error(
                "Error while publishing messages in {$this->projectName}. Message: Resource - [{$messageAttributes[Message::EVENT_TYPE]}], ID - [{$messageAttributes[Message::EVENT_OBJECT_ID]}]. Cause: [{$dispatchedMessage->getDispatchReason()}]"
            );
        }

        return $response;
    }
}
