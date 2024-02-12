<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Persistence;

use Profesia\MessagingCore\Broking\Dto\Sending\DispatchedMessage;

interface DispatchedEventRepositoryInterface
{
    public function persist(DispatchedMessage $message): void;

    public function persistBatch(DispatchedMessage...$dispatchedMessages): void;
}
