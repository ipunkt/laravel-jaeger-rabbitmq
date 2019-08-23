<?php namespace Ipunkt\LaravelJaegerRabbitMQ;

use DB;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\ServiceProvider;
use Event;
use Ipunkt\LaravelJaegerRabbitMQ\Context\EmptyMessageContext;
use Ipunkt\LaravelJaegerRabbitMQ\EventHandler\ReceiveEventHandler;
use Ipunkt\LaravelJaegerRabbitMQ\EventHandler\SendEventHandler;
use Ipunkt\RabbitMQ\Events\MessageCausedException;
use Ipunkt\RabbitMQ\Events\MessageProcessed;
use Ipunkt\RabbitMQ\Events\MessageReceived;
use Ipunkt\RabbitMQ\Events\MessageSending;
use Ipunkt\RabbitMQ\Events\MessageSent;
use Log;

/**
 * Class Provider
 * @package Ipunkt\LaravelJaegerRabbitMQ
 */
class Provider extends ServiceProvider
{
    public function register()
    {
        $this->app->instance('message.context', new EmptyMessageContext());
    }

    public function boot()
    {
        Event::listen(MessageReceived::class, ReceiveEventHandler::class.'@messageReceived');

        Event::listen(MessageProcessed::class, ReceiveEventHandler::class.'@messageProcessed');

        Event::listen(MessageCausedException::class, ReceiveEventHandler::class.'@messageCausedException');

        Event::listen(MessageSending::class, SendEventHandler::class.'@messageSending');

        Event::listen(MessageSent::class, SendEventHandler::class.'@messageSent');
    }

}