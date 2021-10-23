<?php
/**
 * User: York <lianyupeng1988@126.com>
 * Date: 2017-07-10 16:53
 * @noinspection PhpUnused
 */

namespace Poa\Middleware;

/**
 * 用于测量运行时间中间件
 */
class ElapsedTimeMiddleware implements MiddlewareInterface
{
    /** {@inheritdoc} */
    public function __invoke(ContextInterface $context)
    {
        $begin = microtime(true);
        $data['begin_sec'] = $begin;
        $context->setData('elapsedTime', $data);
        yield; // 让出执行
        $end = microtime(true);
        $used = $end - $begin;
        $data['end_sec'] = $end;
        $data['used_sec'] = $used;
        $data['used_msec'] = ceil($used * 1000);
        $context->setData('elapsedTime', $data);
    }
}
