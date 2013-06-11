<?php
namespace AmqpSmartMessage\Amqplib;

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Channel\AMQPChannel;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use AmqpSmartMessage\Event\ProcessEvent;
use AmqpSmartMessage\Event\MessageEvent;
use AmqpSmartMessage\MessageSerializerInterface;

/**
 * Message consumer. Consumes messages from RabbitMQ queue
 *
 * @see ThumperConnection
 */
class MessageConsumer
{
    protected $amq;
    protected $serializer;
    protected $eventDispatcher;
    protected $exchange;


    /**
     * @var AMQPChannel
     */
    protected $channel;

    protected $consumerTag;
    protected $lastMessage;

    public function __construct(
        AMQPConnection $amq,
        MessageSerializerInterface $serializer,
        EventDispatcherInterface $eventDispatcher,
        $exchange
    )
    {
        $this->amq             = $amq;
        $this->serializer      = $serializer;
        $this->eventDispatcher = $eventDispatcher;
        $this->exchange        = $exchange;
    }


    /**
     * Consumes messages
     */
    public function consume()
    {
        $ch = $this->getChannel();
        $ch->exchange_declare($this->exchange, 'topic', false, true, false);
        $ch->queue_declare($this->getQueueName(), false, true, false, false);
        $ch->queue_bind($this->getQueueName(), $this->exchange, $this->getRoutingKey());
        $ch->basic_consume(
            $this->getQueueName(),
            $this->getConsumerTag(),
            false,
            false,
            false,
            false,
            array($this, 'processMessage')
        );
        while(count($ch->callbacks)) {
            $ch->wait();
        }
    }

    /**
     * Message callback method
     *
     * @param $message
     */
    public function processMessage($message)
    {
        $this->lastMessage = $message;
        list($messageName, $args, $meta) = $this->serializer->unserialize($message->body);
        $this->eventDispatcher->dispatch($messageName, new MessageEvent($args, $meta));
        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
        $this->eventDispatcher->dispatch(
            ProcessEvent::MESSAGE_HANDLED,
            new ProcessEvent($messageName, $meta, $message)
        );
    }

    public function getLastMessage()
    {
        return $this->lastMessage;
    }

    /**
     * Gets routing key
     *
     * @return string
     */
    protected function getRoutingKey()
    {
        return $this->exchange . '.#';
        //return $this->exchange . '.chunk_emit';
    }

    /**
     * Gets queue name
     *
     * @return string
     */
    protected function getQueueName()
    {
        return $this->exchange . '-queue';
    }

    /**
     * Gets AMQ consumer tag
     *
     * @return string
     */
    public function getConsumerTag()
    {
        if (null === $this->consumerTag) {
            $this->consumerTag = 'PHP_' . getmypid();
        }
        return $this->consumerTag;
    }

    /**
     * Sets consumer tag
     *
     * @param string $consumerTag Consumer tag for RabbitMQ
     */
    public function setConsumerTag($consumerTag)
    {
        $this->consumerTag = $consumerTag;
    }

    /**
     * Gets consumer AMQ channel
     *
     * @return AMQPChannel
     */
    protected function getChannel()
    {
        if (null === $this->channel) {
            $this->channel = $this->amq->channel();
        }
        return $this->channel;
    }
}
