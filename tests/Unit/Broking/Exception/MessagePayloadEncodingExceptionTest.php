<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Test\Unit\Broking\Exception;

use PHPUnit\Framework\TestCase;
use Profesia\MessagingCore\Broking\Exception\MessagePayloadEncodingException;
use Profesia\MessagingCoreContracts\Exception\AbstractRuntimeException;
use RuntimeException;

class MessagePayloadEncodingExceptionTest extends TestCase
{
    public function testIsPartOfTheRuntimeExceptionHierarchy(): void
    {
        $exception = new MessagePayloadEncodingException('boom');

        $this->assertInstanceOf(AbstractRuntimeException::class, $exception);
        $this->assertInstanceOf(RuntimeException::class, $exception);
        $this->assertEquals('boom', $exception->getMessage());
    }
}
