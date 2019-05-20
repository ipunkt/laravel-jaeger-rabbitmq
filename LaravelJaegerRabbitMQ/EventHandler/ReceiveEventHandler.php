<?php namespace Ipunkt\LaravelJaegerRabbitMQ\EventHandler;

use Interop\Amqp\AmqpMessage;
use Ipunkt\RabbitMQ\Events\MessageProcessed;
use Ipunkt\RabbitMQ\Events\MessageReceived;
use Jaeger\Config;
use Jaeger\SpanContext;
use const OpenTracing\Formats\TEXT_MAP;
use OpenTracing\Reference;

/**
 * Class ReceiveEventHandler
 * @package Ipunkt\LaravelJaegerRabbitMQ\EventHandler
 */
class ReceiveEventHandler
{

    /**
     * @var Config
     */
    private $config;

    /**
     * @var \Jaeger\Jaeger
     */
    private $tracer;

    /**
     * @var \Jaeger\Span
     */
    private $globalSpan;

    /**
     * @var AmqpMessage
     */
    private $message;

    /**
     * @var SpanContext
     */
    private $spanContext;

    public function messageReceived(MessageReceived $messageReceived)
    {
        $this->message = $messageReceived->getMessage();

        $this->setConfig();

        $this->buildTracer();

        $this->extractContext();

        $this->buildGlobalSpan();
    }

    public function messageProcessed(MessageProcessed $messageProcessed)
    {
        app('context.tracer.globalSpan')->finish();
        app('context.tracer')->flush();
    }

    protected function setConfig(): void
    {
        // Get the base config object
        $this->config = Config::getInstance();
    }

    protected function buildTracer(): void
    {
        // Start the tracer with a service name and the jaeger address
        $this->tracer = $this->config->initTrace(config('app.name'), config('jaeger.host'));

        // Set the tracer as a singleton in the IOC container
        \App::instance('context.tracer', $this->tracer);
    }

    private function extractContext()
    {
        $this->resetContext();

        if( str_contains($this->message->getContentType(), 'json') ) {
            $this->extractContextFromJsonBody();
        }
    }

    private function resetContext()
    {
        $this->spanContext = null;
    }

    private function extractContextFromJsonBody()
    {
        $body = $this->message->getBody();
        $bodyContent = json_decode($body);

        if( !array_key_exists('trace', $bodyContent) )
            return;


        $traceContent = $bodyContent['trace'];
        $this->spanContext = $this->tracer->extract(TEXT_MAP, $traceContent);
    }

    /**
     * @var array
     */
    private $spanOptions = [];

    /**
     * @param MessageReceived $messageReceived
     */
    protected function buildGlobalSpan(): void
    {
        $routingKey = $this->message->getRoutingKey();

        $this->buildSpanOptions();

        // Start the global span, it'll wrap the request/console lifecycle
        $this->globalSpan = $this->tracer->startSpan($routingKey, $this->spanOptions);

        // Set the uuid as a tag for this trace
        $this->globalSpan->setTags([
            'uuid' => app('context.uuid')->toString(),
            'environment' => config('app.env')
        ]);

        \App::instance('context.tracer.globalSpan', $this->globalSpan);
    }

    private function buildSpanOptions()
    {
        $this->spanOptions = [];

        $this->addChildOfSpanOption();
    }

    private function addChildOfSpanOption()
    {
        $spanContextSet = ($this->spanContext instanceof SpanContext);
        if( !$spanContextSet )
            return;

        $this->spanContext[Reference::CHILD_OF] = $this->spanContext;
    }


}