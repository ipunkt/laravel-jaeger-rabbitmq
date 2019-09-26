<?php namespace Ipunkt\LaravelJaegerRabbitMQ\EventHandler;

use App;
use Interop\Amqp\AmqpMessage;
use Ipunkt\LaravelJaeger\Context\EmptyContext;
use Ipunkt\LaravelJaeger\Context\MasterSpanContext;
use Ipunkt\LaravelJaeger\Context\SpanContext;
use Ipunkt\LaravelJaegerRabbitMQ\Context\MessageParser;
use Ipunkt\RabbitMQ\Events\MessageCausedException;
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
        $context = app(MasterSpanContext::class);

        app()->instance('context', $context);
        app()->instance('current-context', $context);

        $context->start();
        $this->parseMessage( $messageReceived->getMessage() );
    }

    public function messageProcessed(MessageProcessed $messageProcessed)
    {
        /**
         * @var SpanContext $context
         */
        $context = app('context');
        $context->log(['result' => $messageProcessed->getResult()]);
        $context->setPrivateTags(['result' => $messageProcessed->getResult()]);
        $context->finish();

        app()->instance('context', new EmptyContext());
    }

    public function messageCausedException(MessageCausedException $messageCausedException)
    {
        $exception = $messageCausedException->getThrowable();

        /**
         * @var SpanContext $context
         */
        $context = app('context');
        $context->log([
            'message' => 'Exception thrown',
            'exception-message' => $exception->getMessage(),
            'exception-type' => get_class($exception),
            'exception-code' => $exception->getCode(),
            'exception-file' => $exception->getFile(),
            'exception-line' => $exception->getLine(),
            'exception-trace' => $exception->getTraceAsString(),
        ]);
        $context->setPrivateTags(['error' => 'exception']);
        $context->finish();

        app()->instance('context', new EmptyContext());

    }

	private function parseMessage( AmqpMessage $message ) {
        $this->messageParser
            ->setMessage($message)
            ->setContext( app('context') )
            ->parse();
	}


}