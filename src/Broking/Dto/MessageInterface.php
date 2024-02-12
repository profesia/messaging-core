<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Dto;

/**
 * @deprecated
 */
interface MessageInterface
{
    public function toArray(): array;

    public function getTopic(): string;
}