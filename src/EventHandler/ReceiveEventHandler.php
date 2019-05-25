<?php namespace Ipunkt\LaravelJaegerRabbitMQ\EventHandler;

use App;
use Ipunkt\LaravelJaeger\Context\SpanContext;
use Ipunkt\LaravelJaegerRabbitMQ\Context\EmptyMessageContext;
use Ipunkt\LaravelJaegerRabbitMQ\Context\MessageParser;
use Ipunkt\RabbitMQ\Events\MessageProcessed;
use Ipunkt\RabbitMQ\Events\MessageReceived;

/**
 * Class ReceiveEventHandler
 * @package Ipunkt\LaravelJaegerRabbitMQ\EventHandler
 */
class ReceiveEventHandler
{
    /**
     * @var MessageParser
     */
    private $messageParser;

    /**
     * ReceiveEventHandler constructor.
     * @param MessageParser $messageParser
     */
    public function __construct( MessageParser $messageParser ) {
        $this->messageParser = $messageParser;
    }

    public function messageReceived(MessageReceived $messageReceived)
    {
	    /**
	     * @var SpanContext $context
	     */
        $context = app(SpanContext::class);

        app()->instance('message.context', $context);

        $context->start();
        $this->parseMessage( $messageReceived->getMessage() );
    }

    public function messageProcessed(MessageProcessed $messageProcessed)
    {
        /**
         * @var SpanContext $context
         */
        $context = app('message.context');
        $context->log(['result' => $messageProcessed->getResult()]);
        $context->setPrivateTags(['result' => $messageProcessed->getResult()]);
        $context->finish();

        app()->instance('message.context', new EmptyMessageContext());
    }

	private function parseMessage( \Interop\Amqp\AmqpMessage $message ) {
        $this->messageParser
            ->setMessage($message)
            ->setContext( app('message.context') )
            ->parse();
	}


}