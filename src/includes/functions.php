<?php
/**
 * User: York <lianyupeng1988@126.com>
 * Date: 2017-06-19 16:41
 */

namespace Poa\Middleware;

use Closure;
use Generator;
use Throwable;
use Traversable;

/**
 * 包装执行中间件（或 callable 对象集合）返回的一个闭包函数用于执行。
 *
 * 中间件必须是可被调用的（callable），或者是生成器对象（或函数）；
 *
 * <span color="red">中间件不应该有返回值</span>，如果需要在多个中间件之间传递数据，请通过中间件的入参来进行；
 * 中间件的返回值有特殊含义，false 表示中止后续中间件的执行；
 *
 * 默认所有的中间件都会被执行，可以通过返回 false 来提前中止后续中间件的调用。
 * 在生成器中间件的洋葱圈模型中，只能在进入的<b>第一圈</b>直接返回 false 来中止后续中间件的执行；
 *
 * co 会确保所有已执行的生成器中间件的洋葱圈流程完全执行完毕；
 *
 * 入参也可以是一个数组或迭代器，其中的每一个成员都必须是一个中间件，这是中间件组合成的中间件子系统；
 * 子系统里面的所有中间件流程只有全部执行完毕后才会进入到下一个中间件的执行。
 *
 * @param MiddlewareInterface[]|callable[]|Generator[]|Traversable[] $middlewares
 * @return Closure
 */
function co(...$middlewares): Closure
{
    /**
     * @param array $params
     * @return void|false 中间件不应该有返回值，返回 false 表示中止执行后续的中间件
     * @throws Throwable
     */
    return function (...$params) use ($middlewares) {
        $isDiscontinue = null;        // 是否中止
        $generatorStack = [];         // 生成器栈，用于生成器的回溯过程
        $exception = null;            // 暂存处理过程中的遇到的异常，避免提前终端处理

        try {
            while (!empty($middlewares)) {
                $m = array_shift($middlewares);
                if ($m instanceof Generator) {
                    // 生成器中间件实现了洋葱圈模型
                    $ret = $m->current();
                    if ($m->valid()) {
                        // 生成器中间件入栈
                        $generatorStack[] = $m;
                        if ($ret === false) {
                            // 生成器中间件返回 false，提前中止后续中间件执行
                            // 不能直接 return，需要确保已经入栈的生成器执行收尾操作
                            $isDiscontinue = true;
                            break;
                        }
                    } else {
                        // 生成器中间件已执行完毕，不需要入栈
                        if ($m->getReturn() === false) {
                            // 生成器中间件返回 false，提前中止后续中间件执行
                            // 不能直接 return，需要确保已经入栈的生成器执行收尾操作
                            $isDiscontinue = true;
                            break;
                        }
                    }
                } else if (is_callable($m)) {
                    $r = $m(...$params);
                    if ($r instanceof Generator) {
                        // 如果返回的是一个Generator，则插入到当前中间件队列的头部，在下一个循环中立即执行
                        array_unshift($middlewares, $r);
                    } else if ($r === false) {
                        // 这是一个普通函数调用并且显示返回了 false，结束中间件的执行
                        // 不能直接 return，需要确保已经入栈的生成器执行收尾操作
                        $isDiscontinue = true;
                        break;
                    }
                } else if (is_array($m) || $m instanceof Traversable) {
                    // 中间件构成的子系统，只有里面的所有逻辑执行完后才会进入下一个中间件
                    if (co(...$m)(...$params) === false) {
                        // 子系统返回 false 提前中止
                        // 不能直接 return，需要确保已经入栈的生成器执行收尾操作
                        $isDiscontinue = true;
                        break;
                    }
                }
            }
        } catch (Throwable $throwable) {
            // 中间件执行发生异常，不能中断已入栈的生成器中间件的收尾操作
            // 先把异常暂存下来
            $exception = $throwable;
        }

        // 确保已经入栈的生成器中间件可以完全执行完毕

        while (!empty($generatorStack) > 0) {
            /** @var Generator $g */
            // 数组最末位表示栈顶，最后入栈的 Generator 需要最先执行
            $g = array_pop($generatorStack);
            if ($g->valid()) {
                try {
                    if ($exception) {
                        // 将之前捕获的异常抛进去，方便异常处理器进行处理
                        $g->throw($exception);
                        // 异常一旦被处理就不再继续传递给后续的中间件阶段执行
                        $exception = null;
                    } else {
                        $g->next();
                    }
                    // 不要直接放回栈顶，应该是放到栈尾确保按洋葱圈模型的顺序执行
                    array_unshift($generatorStack, $g);
                } catch (Throwable $throwable) {
                    // 抛出异常的生成器不再重新入栈
                    $exception = $throwable;
                }
            } else {
                $result = $g->getReturn();
                if ($result === false && $isDiscontinue !== true) {
                    $isDiscontinue = true;
                }
                unset($g, $result);
                $g = null;
            }
        }

        if ($exception) {
            // 暂存的异常未被处理，重新抛出
            throw $exception;
        }

        if ($isDiscontinue) return false;
    };
}
