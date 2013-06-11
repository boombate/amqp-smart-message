<?php
namespace Bb8\Amqp\SmartMessage\Event;

use Symfony\Component\EventDispatcher\Event;

class MessageEvent extends Event
{
    protected $args;
    protected $meta;

    public function __construct($args, $meta)
    {
        $this->args = $args;
        $this->meta = $meta;
    }

    public function getArgs()
    {
        return $this->args;
    }

    public function getMeta()
    {
        return $this->meta;
    }
}
