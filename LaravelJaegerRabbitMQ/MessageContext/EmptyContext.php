<?php namespace Ipunkt\LaravelJaegerRabbitMQ\MessageContext;

/**
 * Class EmptyContext
 */
class EmptyContext implements Context
{
    public function finish()
    {

    }

    public function setPrivateTags(array $tags)
    {
    }

    public function setPropagatedTags(array $tags)
    {
    }


    public function inject(array &$messageData) {

    }
}