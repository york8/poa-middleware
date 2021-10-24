<?php
/**
 * User: York <lianyupeng1988@126.com>
 * Date: 2021/10/24 15:53
 */

namespace Poa\Middleware;

interface MiddlewareSystemInterface extends MiddlewareInterface
{
    /**
     * 在洋葱圈外圈添加中间件，将在 handle 之前执行，先添加的优先执行
     * @param MiddlewareInterface|callable $middleware
     * @return self
     * @see before
     */
    public function use($middleware): self;

    /**
     * 在洋葱圈的外圈添加中间件，将在 handle 之前执行，不同于 use，后添加的优先执行
     * @param MiddlewareInterface|callable $middleware
     * @return self
     */
    public function before($middleware): self;

    /**
     * 在洋葱圈的内圈添加中间件，将在 handle 之后执行，先添加的优先执行
     * @param MiddlewareInterface|callable $middleware
     * @return self
     */
    public function after($middleware): self;
}
