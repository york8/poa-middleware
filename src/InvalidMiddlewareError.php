<?php
/**
 * User: York <lianyupeng1988@126.com>
 * Date: 2021/10/23 19:11
 */

namespace Poa\Middleware;

class InvalidMiddlewareError extends \TypeError
{
    public function __construct($message = "The middleware MUST be a subclass of MiddlewareInterface or a callable object")
    {
        parent::__construct($message, -1);
    }
}
