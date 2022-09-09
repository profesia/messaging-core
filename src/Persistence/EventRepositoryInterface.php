<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Persistence;

use Profesia\MessagingCore\Broking\Dto\Message;
use Profesia\MessagingCore\Broking\Dto\MessageCollection;

interface EventRepositoryInterface
{
    public function persist(Message $message): void;
    public function persistBatch(MessageCollection $collection): void;
}
