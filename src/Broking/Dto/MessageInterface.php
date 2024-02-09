<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Dto;

use Profesia\MessagingCore\Exception\AbstractRuntimeException;

interface MessageInterface
{
    /**
     * @return array
     *
     * @throws AbstractRuntimeException
     */
    public function toArray(): array;

    public function encode(): array;

    public function getTopic(): string;
}