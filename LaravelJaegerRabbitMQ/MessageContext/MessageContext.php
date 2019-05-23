<?php namespace Ipunkt\LaravelJaegerRabbitMQ\MessageContext;

use Interop\Amqp\AmqpMessage;
use Ipunkt\LaravelJaegerRabbitMQ\TagPropagator\TagPropagator;
use Jaeger\Config;
use Jaeger\Jaeger;
use OpenTracing\Reference;
use OpenTracing\Span;
use OpenTracing\SpanContext;
use const OpenTracing\Formats\TEXT_MAP;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Class MessageContext
 */
class MessageContext implements Context
{

    /**
     * @var Jaeger
     */
    protected $tracer;

    /**
     * @var Span
     */
    protected $messageSpan;

    /**
     * @var AmqpMessage
     */
    protected $message;

    /**
     * @var \OpenTracing\SpanContext
     */
    protected $spanContext;

    /**
     * @var UuidInterface
     */
    protected $uuid;

    /**
     * @var array
     */
    private $traceContent;
    /**
     * @var TagPropagator
     */
    private $tagPropagator;

    /**
     * MessageContext constructor.
     * @param TagPropagator $tagPropagator
     */
    public function __construct(TagPropagator $tagPropagator) {
        $this->tagPropagator = $tagPropagator;
    }

    public function parseMessage(AmqpMessage $message)
    {
        $this->message = $message;

        $this->start();

        $this->buildMessageSpan();
    }

    public function start()
    {
        $this->buildTracer();
    }

    public function finish()
    {
        $this->messageSpan->finish();
        $this->tracer->flush();
    }

    protected function buildTracer(): void
    {
        $config = Config::getInstance();

        $config->gen128bit();

        // Start the tracer with a service name and the jaeger address
        $this->tracer = $config->initTrace(config('app.name'), config('jaeger.host'));
    }

    private function buildMessageSpan()
    {
        $this->extractContext();

        $routingKey = $this->message->getRoutingKey();

        $this->buildSpanOptions();

        // Start the global span, it'll wrap the request/console lifecycle
        $this->messageSpan = $this->tracer->startSpan($routingKey, $this->spanOptions);

        // Set the uuid as a tag for this trace
        $this->uuid = Uuid::uuid1();
        $this->messageSpan->setTags([
            'uuid' => (string)$this->uuid,
            'environment' => config('app.env')
        ]);
        $this->tagPropagator->apply($this->messageSpan);
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
        $this->tagPropagator->reset();
    }

    private function extractContextFromJsonBody()
    {
        $body = $this->message->getBody();
        $bodyContent = json_decode($body, true);

        if( !array_key_exists('trace', $bodyContent) )
            return;

        $this->traceContent = $bodyContent['trace'];
        $this->extractSpanContext();

        $this->extractPropagatedTags();
    }

    private function extractSpanContext()
    {
        $this->spanContext = $this->tracer->extract(TEXT_MAP, $this->traceContent);
    }

    private function extractPropagatedTags()
    {
        $this->tagPropagator->extract($this->traceContent);
    }

    /**
     * @var array
     */
    private $spanOptions = [];

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

        $this->spanOptions[Reference::CHILD_OF] = $this->spanContext;
    }

    public function setPrivateTags(array $tags)
    {
        $this->messageSpan->setTags($tags);
    }

    public function setPropagatedTags(array $tags)
    {
        $this->tagPropagator->addTags($tags);

        $this->messageSpan->setTags($tags);
    }

    /**
     * @param array $messageData
     */
    public function inject(array &$messageData)
    {
        $context = $this->messageSpan->getContext();

        app('context.tracer')->inject($context, TEXT_MAP, $messageData);

        $this->tagPropagator->inject($messageData);
    }
}