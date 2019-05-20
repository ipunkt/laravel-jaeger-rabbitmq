<?php namespace Ipunkt\LaravelJaegerRabbitMQ;

use Illuminate\Support\ServiceProvider;
use Event;
use Ipunkt\LaravelJaegerRabbitMQ\EventHandler\ReceiveEventHandler;
use Ipunkt\LaravelJaegerRabbitMQ\EventHandler\SendEventHandler;
use Ipunkt\RabbitMQ\Events\MessageProcessed;
use Ipunkt\RabbitMQ\Events\MessageReceived;
use Ipunkt\RabbitMQ\Events\MessageSending;
use Ipunkt\RabbitMQ\Events\MessageSent;

/**
 * Class Provider
 * @package Ipunkt\LaravelJaegerRabbitMQ
 */
class Provider extends ServiceProvider
{

    public function boot()
    {
        Event::listen(MessageReceived::class, ReceiveEventHandler::class.'@messageReceived');

        Event::listen(MessageProcessed::class, ReceiveEventHandler::class.'@messageProcessed');

        Event::listen(MessageSending::class, SendEventHandler::class.'@messageSending');

        Event::listen(MessageSent::class, SendEventHandler::class.'@messageSent');
    }

}