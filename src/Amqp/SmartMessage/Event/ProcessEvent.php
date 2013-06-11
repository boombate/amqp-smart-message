<?php
namespace Bb8\Amqp\SmartMessage\Event;

use Symfony\Component\EventDispatcher\Event;
use PhpAmqpLib\Message\AMQPMessage;

class ProcessEvent extends Event
{
    const MESSAGE_HANDLED = 'amqp.event.message_handled';

    protected $messageName;
    protected $meta;
    protected $message;

    public function __construct($messageName, $meta, AMQPMessage $message)
    {
        $this->messageName = $messageName;
        $this->meta        = $meta;
        $this->message     = $message;
    }

    public function getMessageName()
    {
        return $this->messageName;
    }

    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * @return AMQPMessage
     */
    public function getAmqpMessage()
    {
        return $this->message;
    }
}
