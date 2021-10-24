<?php
/**
 * User: York <lianyupeng1988@126.com>
 * Date: 2021/10/22 16:12
 * @noinspection PhpUnused
 */

namespace Poa\Middleware;

use ArrayAccess;

/**
 * 中间件上下文对象，用来在中间件调度过程中传递和共享数据
 */
interface ContextInterface extends ArrayAccess
{
    public function getData($name);

    public function setData($name, $value);

    public function setDatas(array &$datas);
}
