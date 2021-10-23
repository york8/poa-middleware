<?php
/**
 * User: York <lianyupeng1988@126.com>
 * Date: 2017-07-10 14:15
 */

namespace Poa\Middleware;

/**
 * 中间件接口
 * @package Middleware
 */
interface MiddlewareInterface
{
    /**
     * 中间件对象必须是一个可执行对象，必须实现 __invoke 魔术方法，同直接调用 run 方法;
     * param ContextInterface $context 中间件上下文，用来在中间调用过程中传递和共享数据
     * @return void|false 中间间不能有返回值，返回 false 表示中止执行后续的中间件
     */
    public function __invoke(ContextInterface $context);
}
