<?php
namespace Bb8\Amqp\Supervisor;

use Bb8\Amqp\SmartMessage\Event\ProcessEvent;
use Psr\Log\LoggerInterface;

class Supervisor
{
    protected $logger;
    protected $maxMessageCount;
    protected $maxMemoryUsage;

    protected $lastHandledMessage;
    protected $memoryUsage;
    protected $handledCount = 0;

    public function __construct(
        LoggerInterface $logger = null,
        $maxMessageCount = null,
        $maxMemoryUsage = null
    )
    {
        $this->logger          = $logger;
        $this->maxMessageCount = $maxMessageCount;
        $this->maxMemoryUsage  = $maxMemoryUsage;
    }

    public function onMessageHandled(ProcessEvent $event)
    {
        $this->handledCount++;
        $this->lastHandledMessage = $event->getMessageName();
        $this->memoryUsage        = memory_get_usage();
        echo 'Memory usage:  ' . number_format($this->memoryUsage / 1024, 2, '.', ' ') . 'K' .PHP_EOL;
        if (null !== $this->logger) {
            $this->logMessage($this->logger);
        }
        if ($this->isCancellationRequired()) {
            $event->getAmqpMessage()->delivery_info['channel']->basic_cancel(
                $event->getAmqpMessage()->delivery_info['consumer_tag']
            );
        }
    }

    protected function logMessage(LoggerInterface $logger)
    {
        $logger->info('Last handled:  ' . $this->lastHandledMessage);
        $logger->info('Handled count: ' . $this->handledCount);
        $logger->info('Memory usage:  ' . number_format($this->memoryUsage / 1024, 2, '.', ' ') . 'K');
    }

    protected function isCancellationRequired()
    {
        return (null !== $this->maxMessageCount && $this->handledCount > $this->maxMessageCount)
            || (null !== $this->maxMemoryUsage && $this->memoryUsage > $this->maxMemoryUsage);
    }
}
