<?php namespace Ipunkt\src\Context;

use Ipunkt\LaravelJaeger\Context\Context;
use Ipunkt\LaravelJaeger\Context\EmptyContext;

/**
 * Class EmptyMessageContext
 * @package Ipunkt\LaravelJaegerRabbitMQ\Context
 */
class EmptyMessageContext extends EmptyContext
{
    public function inject(array &$messageData)
    {
        $this->injectGlobalContextData($messageData);
    }

    public function injectGlobalContextData(array &$messageData)
    {
        /**
         * @var Context $globalContext
         */
        $globalContext = app('context');
        $globalContext->inject($messageData);
    }


}