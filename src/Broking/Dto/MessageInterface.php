<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Dto;

interface MessageInterface
{
    public function toArray(): array;
}