<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Decorator;

use Profesia\MessagingCore\Broking\Dto\DispatchedMessage;
use Profesia\MessagingCore\Broking\Dto\Message;
use Profesia\MessagingCore\Broking\MessageBrokerInterface;
use Psr\Log\LoggerInterface;

final class TargetFilteringMessagesLogger extends AbstractMessagesLogger
{
    private string $targetSubstring;

    public function __construct(MessageBrokerInterface $decoratedBroker, LoggerInterface $logger, string $projectName, string $targetSubstring)
    {
        $this->targetSubstring = $targetSubstring;

        parent::__construct($decoratedBroker, $logger, $projectName);
    }

    protected function shouldBeSentMessageLogged(DispatchedMessage $dispatchedMessage): bool
    {
        $data = $dispatchedMessage->getMessage()->toArray();

        $target = $data[Message::EVENT_ATTRIBUTES][Message::EVENT_TARGET];

        return (
            str_contains(
                strtolower($target),
                strtolower($this->targetSubstring)
            ) === false
        );
    }
}