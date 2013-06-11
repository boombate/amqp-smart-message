<?php
namespace AmqpSmartMessage\Amqplib;

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Channel\AMQPChannel;
use AmqpSmartMessage\MessageSerializerInterface;
use AmqpSmartMessage\MessagePublisherInterface;

/**
 * Message publisher. Publishes messages into RabbitMQ queue
 */
class MessagePublisher implements MessagePublisherInterface
{
    protected $amq;
    protected $serializer;
    protected $exchange;

    /**
     * @var AMQPChannel
     */
    protected $channel;

    public function __construct(
        AMQPConnection $amq,
        MessageSerializerInterface $serializer,
        $exchange
    )
    {
        $this->amq        = $amq;
        $this->serializer = $serializer;
        $this->exchange   = $exchange;
    }

    /**
     * Publish smart message into a queue
     *
     * @param string $messageName message name
     * @param mixed  $args        message arguments
     * @param array $meta message metadata if required
     *
     * @return void
     */
    public function publish($messageName, $args, array $meta = array())
    {

        $message = new AMQPMessage(
            $this->serializer->serialize($messageName, $args, $meta),
            array('content_type' => 'text/plain')
        );

        $this->getChannel()->basic_publish($message, $this->exchange, $this->buildRoute($messageName));
    }

    /**
     * Builds queue name from specified message name and injected queue prefix
     *
     * @param $messageName
     *
     * @return string
     */
    protected function buildRoute($messageName)
    {
        return $this->exchange . '.' . $messageName;
    }

    /**
     * Gets publisher AMQ channel
     *
     * @return AMQPChannel
     */
    protected function getChannel()
    {
        if (null === $this->channel) {
            $this->channel = $this->amq->channel();
            $this->channel->exchange_declare($this->exchange, 'topic', false, true, false);
        }
        return $this->channel;
    }
}
