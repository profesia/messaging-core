<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Decorator;

use Profesia\MessagingCore\Broking\Dto\Sending\DispatchedMessage;
use Profesia\MessagingCore\Broking\MessageBrokerInterface;
use Psr\Log\LoggerInterface;

final class TopicFilteringMessagesLogger extends AbstractMessagesLogger
{
    public function __construct(
        MessageBrokerInterface $decoratedBroker,
        LoggerInterface $logger,
        string $projectName,
        private readonly string $topicSubstring
    )
    {
        parent::__construct($decoratedBroker, $logger, $projectName);
    }

    protected function shouldBeSentMessageLogged(DispatchedMessage $dispatchedMessage): bool
    {
        return (
            str_contains(
                strtolower($dispatchedMessage->getTopic()),
                strtolower($this->topicSubstring)
            ) === false
        );
    }
}