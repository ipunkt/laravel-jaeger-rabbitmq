<?php namespace Ipunkt\LaravelJaegerRabbitMQ\EventHandler;

use Interop\Amqp\AmqpMessage;
use Ipunkt\RabbitMQ\Events\MessageSending;
use Ipunkt\RabbitMQ\Events\MessageSent;
use const OpenTracing\Formats\TEXT_MAP;

/**
 * Class SendEventHandler
 * @package Ipunkt\LaravelJaegerRabbitMQ\EventHandler
 */
class SendEventHandler
{
    /**
     * @var AmqpMessage
     */
    protected $message;

    public function messageSending(MessageSending $messageSending)
    {
        $this->message = $messageSending->getMessage();

        $this->addContextToMessage();
    }

    public function messageSent(MessageSent $messageSent)
    {
        // Nothing to do here yet
    }

    private function addContextToMessage()
    {
        if( str_contains($this->message->getContentType(), 'json' ) ) {
            $this->addContextToJsonMessage();
            return;
        }
    }

    /**
     *
     */
    private function addContextToJsonMessage()
    {
        $messageBody = $this->message->getBody();
        $messageContent = json_decode($messageBody, true);

        $traceContent = [];
        $context = app('context.tracer.globalSpan')->getContext();
        app('context.tracer')->inject($context, TEXT_MAP, $traceContent);
        $messageContent['trace'] = $traceContent;
        $this->message->setBody( json_encode($messageContent) );
    }

}