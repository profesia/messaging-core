<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking;

use Profesia\MessagingCore\Broking\Dto\GroupedMessagesCollection;
use Profesia\MessagingCore\Broking\Dto\BrokingBatchResponse;

interface MessageBrokerInterface
{
    /**
     * @param GroupedMessagesCollection $collection
     *
     * @return BrokingBatchResponse
     */
    public function publish(GroupedMessagesCollection $collection): BrokingBatchResponse;
}
