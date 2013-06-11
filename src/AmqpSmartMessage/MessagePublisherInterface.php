<?php
namespace AmqpSmartMessage;

/**
 * Message publisher interface
 */
interface MessagePublisherInterface
{
    /**
     * Publish smart message into a queue
     *
     * @param string $messageName message name
     * @param mixed $args message arguments
     * @param array $meta message metadata if required
     *
     * @return void
     */
    public function publish($messageName, $args, array $meta = array());
}
