<?php namespace Ipunkt\LaravelJaegerRabbitMQ\EventHandler;

use Illuminate\Support\Str;
use Interop\Amqp\AmqpMessage;
use Ipunkt\LaravelJaeger\Context\Context;
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
        if( !Str::contains($this->message->getContentType(), 'json' ) )
            return;

        $messageBody = $this->message->getBody();
        $messageContent = json_decode($messageBody, true);

        $traceContent = [];

        /**
         * @var Context $context
         */
        $context = app('current-context');

        $context->inject($traceContent);

        $messageContent['trace'] = $traceContent;
        $this->message->setBody( json_encode($messageContent) );
    }

}