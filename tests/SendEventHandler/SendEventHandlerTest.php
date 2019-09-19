<?php namespace Ipunkt\LaravelJaegerRabbitMQTests\SendEventHandler;

use Interop\Amqp\Impl\AmqpMessage;
use Ipunkt\LaravelJaeger\Context\Context;
use Ipunkt\LaravelJaeger\Context\SpanContext;
use Ipunkt\LaravelJaegerRabbitMQ\EventHandler\SendEventHandler;
use Ipunkt\LaravelJaegerRabbitMQTests\TestCase;
use Ipunkt\RabbitMQ\Events\MessageSending;
use Jaeger\Config;
use Mockery;
use OpenTracing\Span;
use OpenTracing\Tracer;

/**
 * Class SendEventHandlerTest
 * @package Ipunkt\LaravelJaegerRabbitMQTests\SendEventHandler
 */
class SendEventHandlerTest extends TestCase {

	/**
	 * @var SendEventHandler
	 */
	protected $sendEventHandler;

	public function setUp() : void {
		$this->sendEventHandler = new SendEventHandler();
		parent::setUp();
	}

	/**
	 * @test
	 */
	public function messageSendingPropagatedTags(  ) {
		$amqpMessage = new AmqpMessage();
		$amqpMessage->setContentType('application/json');
		$amqpMessage->setBody(json_encode([]));

		$messageSending = new MessageSending($amqpMessage);

		$mockTracer = Mockery::mock(Tracer::class);
		$mockTracer->shouldReceive('initTrace');
		$mockSpan = Mockery::mock(Span::class);
		$mockConfig = Mockery::mock(Config::class);
		$mockConfig->shouldReceive('initTrace')->andReturn($mockTracer);

		Config::$instance = $mockConfig;
		/**
		 * @var Context $context
		 */
		$context = app(SpanContext::class);
		$this->app->instance('context', $context);
        $this->app->instance('current-context', $context);

		$context->setPropagatedTags([
			'tag1' => 'value1'
		]);

		$this->sendEventHandler->messageSending($messageSending);
		dd( $amqpMessage->getBody() );
	}

}