<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Dto\Receiving;

interface ReceivedMessageInterface
{
    public function getEventType(): string;

    public function getSubscribeName(): string;

    public function getDecodedMessage(): array;
}