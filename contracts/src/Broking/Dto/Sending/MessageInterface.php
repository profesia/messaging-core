<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Dto\Sending;

interface MessageInterface
{
    public function encode(): array;

    public function toArray(): array;

    public function getTopic(): string;

    public function getAttributes(): array;

    public function getData(): array;
}