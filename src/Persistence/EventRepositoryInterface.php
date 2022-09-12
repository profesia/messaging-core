<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Persistence;

use Profesia\MessagingCore\Broking\Dto\Message;

interface EventRepositoryInterface
{
    public function persist(Message $message): void;

    public function persistBatch(Message ...$messages): void;
}
