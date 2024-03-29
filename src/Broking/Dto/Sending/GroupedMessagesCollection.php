<?php

declare(strict_types=1);

namespace Profesia\MessagingCore\Broking\Dto\Sending;

use Profesia\MessagingCore\Broking\Exception\TopicIsNotRegisteredException;

final class GroupedMessagesCollection
{
    /**
     * @param MessagesForTopic[] $messagesForTopic
     */
    private function __construct(
        private readonly array $messagesForTopic
    )
    {
    }

    public static function createFromMessages(MessageInterface ...$messages): GroupedMessagesCollection
    {
        $groupedMessages = [];
        foreach ($messages as $message) {
            $topic = $message->getTopic();
            if (array_key_exists($topic, $groupedMessages) === false) {
                $groupedMessages[$topic] = [];
            }

            $groupedMessages[$topic][] = $message;
        }

        $messagesForTopic = [];
        foreach ($groupedMessages as $topic => $group) {
            $messagesForTopic[$topic] = MessagesForTopic::createFromTopicAndMessages($topic, ...$group);
        }

        return new self(
            $messagesForTopic
        );
    }

    /**
     * @param string $topic
     * @return MessageInterface[]
     */
    public function getMessagesForTopic(string $topic): array
    {
        if (array_key_exists($topic, $this->messagesForTopic) === false) {
            throw new TopicIsNotRegisteredException("Topic with name: [{$topic}] is not registered");
        }

        return $this->messagesForTopic[$topic]->getMessages();
    }

    /**
     * @return string[]
     */
    public function getTopics(): array
    {
        return array_keys($this->messagesForTopic);
    }
}
