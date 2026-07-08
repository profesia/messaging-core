<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Test\Unit\Broking\Exception;

use PHPUnit\Framework\TestCase;
use Profesia\MessagingCore\Broking\Exception\ReceivedMessageBadStructureException;
use Profesia\MessagingCoreContracts\Exception\AbstractRuntimeException;
use RuntimeException;

class ReceivedMessageBadStructureExceptionTest extends TestCase
{
    public function testIsPartOfTheRuntimeExceptionHierarchy(): void
    {
        $exception = new ReceivedMessageBadStructureException('boom');

        $this->assertInstanceOf(AbstractRuntimeException::class, $exception);
        $this->assertInstanceOf(RuntimeException::class, $exception);
        $this->assertEquals('boom', $exception->getMessage());
    }
}
