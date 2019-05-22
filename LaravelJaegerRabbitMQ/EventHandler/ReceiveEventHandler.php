<?php namespace Ipunkt\LaravelJaegerRabbitMQ\EventHandler;

use App;
use Ipunkt\LaravelJaegerRabbitMQ\MessageContext\EmptyContext;
use Ipunkt\LaravelJaegerRabbitMQ\MessageContext\MessageContext;
use Ipunkt\RabbitMQ\Events\MessageProcessed;
use Ipunkt\RabbitMQ\Events\MessageReceived;

/**
 * Class ReceiveEventHandler
 * @package Ipunkt\LaravelJaegerRabbitMQ\EventHandler
 */
class ReceiveEventHandler
{

    public function messageReceived(MessageReceived $messageReceived)
    {
        $context = new MessageContext();

        App::instance('message.context', $context);

        $context->parseMessage( $messageReceived->getMessage() );
    }

    public function messageProcessed(MessageProcessed $messageProcessed)
    {
        app('message.context')->finish();

        App::instance('message.context', new EmptyContext());
    }


}