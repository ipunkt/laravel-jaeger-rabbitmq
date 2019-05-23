<?php namespace Ipunkt\LaravelJaegerRabbitMQ\EventHandler;

use Interop\Amqp\AmqpMessage;
use Ipunkt\LaravelJaegerRabbitMQ\MessageContext\MessageContext;
use Ipunkt\RabbitMQ\Events\MessageSending;
use Ipunkt\RabbitMQ\Events\MessageSent;

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
        $this->addContextToJsonMessage();
    }

    /**
     *
     */
    private function addContextToJsonMessage()
    {
        if( !str_contains($this->message->getContentType(), 'json' ) )
            return;

        $messageBody = $this->message->getBody();
        $messageContent = json_decode($messageBody, true);

        $traceContent = [];

        /**
         * @var MessageContext $messageContext
         */
        $messageContext = app('message.context');

        $messageContext->inject($traceContent);

        $messageContent['trace'] = $traceContent;
        $this->message->setBody( json_encode($messageContent) );
    }

}