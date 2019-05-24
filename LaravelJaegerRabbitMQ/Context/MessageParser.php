<?php namespace Ipunkt\LaravelJaegerRabbitMQ\Context;

use Interop\Amqp\AmqpMessage;
use Ipunkt\LaravelJaeger\Context\Context;

/**
 * Class MessageParser
 * @package Ipunkt\LaravelJaegerRabbitMQ\Context
 */
class MessageParser
{

    /**
     * @var AmqpMessage
     */
    protected $message;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var string
     */
    protected $routingKey = '';

    /**
     * @var array
     */
    protected $content = [];

    public function parse()
    {
        $this->parseRoutingKey();

        $this->parseContent();

        $this->context->parse( $this->routingKey, $this->content);

        $this->logMessageContent();
    }

    private function parseRoutingKey()
    {
        $this->routingKey = $this->message->getRoutingKey();
    }

    private function parseContent()
    {
        $contentJson = $this->message->getBody();
        $this->content = json_decode($contentJson, true);
    }

    private function logMessageContent()
    {
        $this->context->log([
            'routing-key' => $this->routingKey,
            'content' => $this->message->getBody(),
            'content-type' => $this->message->getContentType(),
        ]);
    }

    /**
     * @param AmqpMessage $message
     * @return MessageParser
     */
    public function setMessage(AmqpMessage $message): MessageParser
    {
        $this->message = $message;
        return $this;
    }

    /**
     * @param Context $context
     * @return MessageParser
     */
    public function setContext(Context $context): MessageParser
    {
        $this->context = $context;
        return $this;
    }

}