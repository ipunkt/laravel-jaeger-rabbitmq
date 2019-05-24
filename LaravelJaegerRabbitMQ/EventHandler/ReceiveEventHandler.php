<?php namespace Ipunkt\LaravelJaegerRabbitMQ\EventHandler;

use App;
use Ipunkt\LaravelJaeger\Context\SpanContext;
use Ipunkt\LaravelJaegerRabbitMQ\MessageContext\EmptyContext;
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
	    /**
	     * @var SpanContext $context
	     */
        $context = app(SpanContext::class);

        App::instance('message.context', $context);

        $context->start();
        $this->parseMessage( $messageReceived->getMessage() );
    }

    public function messageProcessed(MessageProcessed $messageProcessed)
    {
        app('message.context')->finish();

        App::instance('message.context', new EmptyContext());
    }

	private function parseMessage( \Interop\Amqp\AmqpMessage $message ) {
    	$routingKey = $message->getRoutingKey();

    	$contentJson = $message->getBody();
    	$content = json_decode($contentJson, true);

		app('message.context')->parse( $routingKey, $content);
	}


}