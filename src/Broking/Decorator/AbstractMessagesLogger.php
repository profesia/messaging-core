<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Decorator;

use Profesia\MessagingCore\Broking\Dto\Sending\AbstractMessage;
use Profesia\MessagingCore\Broking\Dto\Sending\BrokingBatchResponse;
use Profesia\MessagingCore\Broking\Dto\Sending\DispatchedMessage;
use Profesia\MessagingCore\Broking\Dto\Sending\GroupedMessagesCollection;
use Profesia\MessagingCore\Broking\MessageBrokerInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractMessagesLogger implements MessageBrokerInterface
{
    public function __construct(
        private readonly MessageBrokerInterface $decoratedBroker,
        private readonly LoggerInterface $logger,
        private readonly string $projectName
    )
    {
    }


    public function publish(GroupedMessagesCollection $collection): BrokingBatchResponse
    {
        $response           = $this->decoratedBroker->publish($collection);
        $dispatchedMessages = $response->getDispatchedMessages();

        foreach ($dispatchedMessages as $dispatchedMessage) {
            if ($dispatchedMessage->wasDispatchedSuccessfully() === true && $this->shouldBeSentMessageLogged($dispatchedMessage)) {
                $this->logger->info(
                    "Message from {$this->projectName} was published",
                    $dispatchedMessage->getEventData()
                );

                continue;
            }

            if ($dispatchedMessage->wasDispatchedSuccessfully() === true) {
                continue;
            }

            $messageAttributes = $dispatchedMessage->getEventAttributes();
            $this->logger->error(
                "Error while publishing messages in {$this->projectName}. Message: Resource - [{$messageAttributes[AbstractMessage::EVENT_TYPE]}], ID - [{$messageAttributes[AbstractMessage::EVENT_OBJECT_ID]}]. Cause: [{$dispatchedMessage->getDispatchReason()}]"
            );
        }

        return $response;
    }

    protected function getProjectName(): string
    {
        return $this->projectName;
    }

    protected function getDecoratedBroker(): MessageBrokerInterface
    {
        return $this->decoratedBroker;
    }

    protected function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    protected abstract function shouldBeSentMessageLogged(DispatchedMessage $dispatchedMessage): bool;
}