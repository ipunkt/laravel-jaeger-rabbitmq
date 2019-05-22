<?php namespace Ipunkt\LaravelJaegerRabbitMQ;

use DB;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\ServiceProvider;
use Event;
use Ipunkt\LaravelJaegerRabbitMQ\EventHandler\ReceiveEventHandler;
use Ipunkt\LaravelJaegerRabbitMQ\EventHandler\SendEventHandler;
use Ipunkt\LaravelJaegerRabbitMQ\MessageContext\EmptyContext;
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
        $this->app->instance('message.context', new EmptyContext());
    }

    public function boot()
    {
        Event::listen(MessageReceived::class, ReceiveEventHandler::class.'@messageReceived');

        Event::listen(MessageProcessed::class, ReceiveEventHandler::class.'@messageProcessed');

        Event::listen(MessageSending::class, SendEventHandler::class.'@messageSending');

        Event::listen(MessageSent::class, SendEventHandler::class.'@messageSent');

        $this->registerEvents();
    }

    protected function registerEvents(): void
    {
        // When the app terminates we must finish the global span
        // and send the trace to the jaeger agent.
        app()->terminating(function () {
            app('message.context')->finish();
        });

        // Listen for each logged message and attach it to the global span
        Event::listen(MessageLogged::class, function (MessageLogged $e) {
            app('context.tracer.globalSpan')->log((array)$e);
        });

        // Also listen for queries and log then,
        // it also receives the log in the MessageLogged event above
        DB::listen(function ($query, $values) {
            Log::debug("[DB Query] {$query->connection->getName()}", [
                'query' => str_replace('"', "'", $query->sql),
                'values' => $values,
                'time' => $query->time . 'ms',
            ]);
        });
    }
}