<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Test\Integration\Broking\Dto\Sending\Factory;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Profesia\MessagingCore\Broking\Dto\Sending\Factory\PubSubMessageFactory;
use Profesia\MessagingCore\Broking\Dto\Sending\PubSubMessage;
use Profesia\MessagingCoreContracts\Broking\Dto\Sending\MessageInterface;

class PubSubMessageFactoryTest extends TestCase
{
    public static function provideDataForCreationTest(): array
    {
        return [
            'event1' => [
                [
                    'resource'        => 'resource1',
                    'eventType'       => 'eventType1',
                    'provider'        => 'provider1',
                    'objectId'        => 'objectId1',
                    'eventOccurredOn' => new DateTimeImmutable(),
                    'correlationId'   => 'correlationId1',
                    'subscribeName'   => 'subscribeName1',
                    'topic'           => 'topicName1',
                    'payload'         => [
                        1,
                    ],
                ],
            ],
            'event2' => [
                [
                    'resource'        => 'resource2',
                    'eventType'       => 'eventType2',
                    'provider'        => 'provider2',
                    'objectId'        => 'objectId2',
                    'eventOccurredOn' => new DateTimeImmutable(),
                    'correlationId'   => 'correlationId2',
                    'subscribeName'   => 'subscribeName2',
                    'topic'           => 'topicName2',
                    'payload'         => [
                        'key' => 'value',
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('provideDataForCreationTest')]
    public function testCanCreatePubSubMessage(array $data): void
    {
        $factory = new PubSubMessageFactory();

        $message = $factory->create(
            $data['resource'],
            $data['eventType'],
            $data['provider'],
            $data['objectId'],
            $data['eventOccurredOn'],
            $data['correlationId'],
            $data['subscribeName'],
            $data['topic'],
            $data['payload'],
        );

        $expected = new PubSubMessage(
            $data['resource'],
            $data['eventType'],
            $data['provider'],
            $data['objectId'],
            $data['eventOccurredOn'],
            $data['correlationId'],
            $data['subscribeName'],
            $data['topic'],
            $data['payload'],
        );

        $this->assertInstanceOf(PubSubMessage::class, $message);
        $this->assertInstanceOf(MessageInterface::class, $message);
        $this->assertEquals($expected, $message);
        $this->assertEquals($expected->toArray(), $message->toArray());
        $this->assertEquals($expected->getTopic(), $message->getTopic());
    }
}
