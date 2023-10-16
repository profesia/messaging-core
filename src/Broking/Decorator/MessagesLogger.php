<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Decorator;

use Profesia\MessagingCore\Broking\Dto\DispatchedMessage;

final class MessagesLogger extends AbstractMessagesLogger
{
    protected function shouldBeSentMessageLogged(DispatchedMessage $dispatchedMessage): bool
    {
        return true;
    }
}
