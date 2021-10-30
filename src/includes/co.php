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
use TypeError;

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
                LOOP:
                $ret = null;
                if ($m instanceof Generator) {
                    // 生成器中间件实现了洋葱圈模型
                    $m->current();
                    if ($m->valid()) {
                        // 生成器中间件入栈
                        $generatorStack[] = $m;
                        continue;
                    } else {
                        // 生成器中间件已执行完毕，不需要入栈
                        $ret = $m->getReturn();
                    }
                } else if (is_callable($m)) {
                    $ret = $m(...$params);
                    if ($ret instanceof Generator) {
                        // 如果返回的是一个Generator，则插入到当前中间件队列的头部，在下一个循环中立即执行
                        array_unshift($middlewares, $ret);
                        continue;
                    }
                } else if (is_array($m) || $m instanceof Traversable) {
                    // 中间件构成的子系统，只有里面的所有逻辑执行完后才会进入下一个中间件
                    $ret = co(...$m)(...$params);
                }
                if ($ret === false) {
                    // 生成器中间件返回 false，提前中止后续中间件执行
                    // 不能直接 return，需要确保已经入栈的生成器执行收尾操作
                    $isDiscontinue = true;
                    break;
                } else if ($ret instanceof Generator
                    || is_callable($ret)
                    || $ret instanceof Traversable
                    || (is_array($ret) && !empty($ret))
                ) {
                    // 返回一个生成器、或可执行对象、或可遍历对象或数组
                    $m = $ret;
                    unset($ret);
                    goto LOOP;
                }
                unset($ret);
            }
        } catch (Throwable $throwable) {
            // 中间件执行发生异常，不能中断已入栈的生成器中间件的收尾操作
            // 先把异常暂存下来
            $exception = $throwable;
        }

        // 确保已经入栈的生成器中间件可以完全执行完毕

        while (!empty($generatorStack)) {
            /** @var Generator $generator */
            // 数组最末位表示栈顶，最后入栈的 Generator 需要最先执行
            $generator = array_pop($generatorStack);
            if ($generator->valid()) {
                try {
                    if ($exception) {
                        // 将之前捕获的异常抛进去，方便异常处理器进行处理
                        $generator->throw($exception);
                        // 异常一旦被处理就不再继续传递给后续的中间件阶段执行
                        $exception = null;
                    } else {
                        $generator->next();
                    }
                    // 不要直接放回栈顶，应该是放到栈尾确保按洋葱圈模型的顺序执行
                    array_unshift($generatorStack, $generator);
                } catch (Throwable $throwable) {
                    // 抛出异常的生成器不再重新入栈
                    $exception = $throwable;
                }
            } else {
                $result = $generator->getReturn();
                if ($result === false && $isDiscontinue !== true) {
                    $isDiscontinue = true;
                }
                unset($generator, $result);
                $generator = null;
            }
        }

        if ($exception) {
            // 暂存的异常未被处理，重新抛出
            throw $exception;
        }

        if ($isDiscontinue) return false;
    };
}

/**
 * 发起一个协作，协作者必须是一个可执行对象或者一个可遍历的对象，其中第一个入参将作为协作的发起者，后续的其它作为协作的参与者。
 *
 * 发起者作为协作的发起方，用于生产整个协作需要的数据等内容给其它参与者进行进行处理；
 *
 * 参与者按添加的顺序先后执行，入参为发起者每一轮迭代生产的数据等内容；参与者必须是一个可执行函数，或者是一个生成器函数；
 * 每次参与者对数据等内容处理后返回的数据内容（不能是 null 和 布尔值）将作为入参传递给下一个参与者，
 * 如果参与者不返回处理结果，则发起者这一轮生产的数据或上一个参与者处理后的结果传递给下一个参与者。
 *
 * 普通可执行函数每一轮迭代将重新执行，入参为前一个参与者返回的处理结果；
 * 生成器函数需有<b color=red>两次的 yield</b>，第一次用来接收前一个参与者的处理结果，下一个 yield 用来返回它自己的处理结果。
 *
 * 参与者显示返回 <b>false</b> 表示中止当前迭代开始下一轮数据处理，此时后面的参与者在将不会执行。
 *
 * 该协作函数将返回一个闭包，如果发起者是一个可执行函数，那闭包的入参将作为发起者的入参，否则该入参没有任何意义。
 *
 * @param array|callable|Traversable $starter 协作的发起者
 * @param callable|Generator ...$callableList 协作的参与者
 *
 * @return Closure
 */
function co2($starter, ...$callableList): Closure
{
    if (!($starter instanceof Traversable) && !is_callable($starter) && !is_array($starter)) {
        throw new TypeError('The Starter MUST BE callable or Traversable, or an Array');
    }
    return function (...$params) use ($starter, $callableList) {
        if (is_callable($starter)) {
            $starter = $starter(...$params);
            if (!($starter instanceof Traversable)) {
                throw new TypeError('The callable Starter returns MUST be Traversable');
            }
        }
        foreach ($starter as $value) {
            $currParams = &$value; // 传递给参与者的入参
            foreach ($callableList as &$g) {
                if (!$g) continue;
                if (is_callable($g)) {
                    // 可执行对象执行运行后需要返回 生成器
                    $r = $g($currParams);
                    if ($r === false) {
                        break; // 中止这一轮的迭代处理
                    } else if (!$r instanceof Generator) {
                        // 其它返回结果将作为下一个参与者的入参
                        if ($r !== true && !is_null($r)) $currParams = &$r;
                        unset($r); // 解引用
                        continue;
                    }
                    $g = $r;
                    unset($r);
                    $g->current();                // 初始化运行生成器
                }
                $r = $g->send($currParams);       // 传递入参，触发执行
                $g->next();                       // 结束当前迭代，等待下一轮数据
                if ($r === false) break;          // 返回 false 表示中止当前迭代
                if (!is_bool($r) && !is_null($r)) $currParams = &$r;
                unset($r);                        // 解引用
                if (!$g->valid()) $g = null;      // 生成器已结束运行，退出协作队列
            }
        }
    };
}
