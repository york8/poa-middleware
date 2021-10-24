<?php
/**
 * User: York <lianyupeng1988@126.com>
 * Date: 2021/10/22 21:21
 */

namespace Poa\Middleware;

class SimpleMiddlewareSystemTrait implements MiddlewareSystemInterface
{
    use MiddlewareSystemTrait;

    public function handle(ContextInterface $context)
    {
    }
}
