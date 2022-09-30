<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking;

use Profesia\MessagingCore\Broking\Dto\MessageCollection;
use Profesia\MessagingCore\Broking\Dto\BrokingBatchResponse;

interface MessageBrokerInterface
{
    /**
     * @param MessageCollection $collection
     *
     * @return BrokingBatchResponse
     */
    public function publish(MessageCollection $collection): BrokingBatchResponse;
}
