<?php namespace Ipunkt\LaravelJaegerRabbitMQ\MessageContext;

/**
 * Interface Context
 * @package Ipunkt\LaravelJaegerRabbitMQ\MessageContext
 */
interface Context
{
    function finish();

    function setPrivateTags(array $tags);

    function setPropagatedTags(array $tags);

    function inject(array &$messageData);

}