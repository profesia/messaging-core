<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Decorator;

use Profesia\MessagingCore\Broking\Dto\BrokingBatchResponse;
use Profesia\MessagingCore\Broking\Dto\DispatchedMessage;
use Profesia\MessagingCore\Broking\Dto\Message;
use Profesia\MessagingCore\Broking\Dto\GroupedMessagesCollection;
use Profesia\MessagingCore\Broking\MessageBrokerInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractMessagesLogger implements MessageBrokerInterface
{
    private string                 $projectName;
    private MessageBrokerInterface $decoratedBroker;
    private LoggerInterface        $logger;

    public function __construct(MessageBrokerInterface $decoratedBroker, LoggerInterface $logger, string $projectName)
    {
        $this->decoratedBroker = $decoratedBroker;
        $this->logger          = $logger;
        $this->projectName     = $projectName;
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
                "Error while publishing messages in {$this->projectName}. Message: Resource - [{$messageAttributes[Message::EVENT_TYPE]}], ID - [{$messageAttributes[Message::EVENT_OBJECT_ID]}]. Cause: [{$dispatchedMessage->getDispatchReason()}]"
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