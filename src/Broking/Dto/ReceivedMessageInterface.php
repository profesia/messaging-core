<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Dto;

interface ReceivedMessageInterface
{
    public function getEventType(): string;

    public function getDecodedMessage(): array;
}