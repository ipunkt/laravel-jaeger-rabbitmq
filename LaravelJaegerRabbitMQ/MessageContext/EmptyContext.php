<?php namespace Ipunkt\LaravelJaegerRabbitMQ\MessageContext;

/**
 * Class EmptyContext
 */
class EmptyContext
{
    public function finish()
    {

    }

    public function setServiceTags(array $tags)
    {
    }

    public function setPropagatedTags(array $tags)
    {
    }


    public function inject(array &$messageData) {

    }
}