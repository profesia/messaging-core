<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking;

use Profesia\MessagingCore\Broking\Dto\MessageCollection;
use Profesia\MessagingCore\Broking\Dto\BrokingBatchResponse;
use Profesia\MessagingCore\Broking\Exception\AbstractMessageBrokerException;

interface MessageBrokerInterface
{
    /**
     * @param MessageCollection $collection
     *
     * @return BrokingBatchResponse
     * @throws AbstractMessageBrokerException
     */
    public function publish(MessageCollection $collection): BrokingBatchResponse;
}
