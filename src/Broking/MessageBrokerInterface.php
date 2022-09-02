<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking;

use Profesia\MessagingCore\Broking\Dto\MessageCollection;

interface MessageBrokerInterface
{
    public function publish(MessageCollection $collection): void;
}
