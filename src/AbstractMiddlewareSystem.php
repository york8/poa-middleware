<?php
/**
 * User: York <lianyupeng1988@126.com>
 * Date: 2021/10/22 19:24
 * @noinspection PhpUnused
 */

namespace Poa\Middleware;

/**
 * 中间件系统，支持执行子中间件
 */
abstract class AbstractMiddlewareSystem implements MiddlewareInterface
{
    /** @var MiddlewareInterface[]|callable[] */
    protected array $beforeMiddlewares = [];
    /** @var MiddlewareInterface[]|callable[] */
    protected array $afterMiddlewares = [];

    /**
     * 中间件系统具体的执行逻辑
     * @param ContextInterface $context
     * @return void|false
     */
    public abstract function handle(ContextInterface $context);

    /**
     * 在洋葱圈外圈添加中间件，将在 handle 之前执行，先添加的优先执行
     * @param MiddlewareInterface|callable $middleware
     * @return self
     * @see before
     */
    public function use($middleware): self
    {
        if (!($middleware instanceof MiddlewareInterface) || !is_callable($middleware)) {
            throw new InvalidMiddlewareError();
        }
        if (in_array($middleware, $this->beforeMiddlewares) || $middleware === $this) return $this;
        $this->beforeMiddlewares[] = $middleware;
        return $this;
    }

    /**
     * 在洋葱圈的外圈添加中间件，将在 handle 之前执行，不同于 use，后添加的优先执行
     * @param MiddlewareInterface|callable $middleware
     * @return self
     */
    public function before($middleware): self
    {
        if (!($middleware instanceof MiddlewareInterface) || !is_callable($middleware)) {
            throw new InvalidMiddlewareError();
        }
        if (in_array($middleware, $this->beforeMiddlewares) || $middleware === $this) return $this;
        array_unshift($this->beforeMiddlewares, $middleware);
        return $this;
    }

    /**
     * 在洋葱圈的内圈添加中间件，将在 handle 之后执行，先添加的优先执行
     * @param MiddlewareInterface|callable $middleware
     * @return self
     */
    public function after($middleware): self
    {
        if (!($middleware instanceof MiddlewareInterface) || !is_callable($middleware)) {
            throw new InvalidMiddlewareError();
        }
        if (in_array($middleware, $this->afterMiddlewares) || $middleware === $this) return $this;
        $this->afterMiddlewares[] = $middleware;
        return $this;
    }

    /**
     * 清空外圈的中间件
     * @return self
     */
    public function clearBefore(): self
    {
        $this->beforeMiddlewares = [];
        return $this;
    }

    /**
     * 清空内圈的中间件
     * @return self
     */
    public function clearAfter(): self
    {
        $this->afterMiddlewares = [];
        return $this;
    }

    /**
     * 清空所有已添加的中间件
     * @return self
     */
    public function clear(): self
    {
        $this->beforeMiddlewares = [];
        $this->afterMiddlewares = [];
        return $this;
    }

    public function run(ContextInterface $context)
    {
        $middlewares = $this->beforeMiddlewares;
        $middlewares[] = [$this, 'handle'];
        return co(...$middlewares, ...$this->afterMiddlewares)($context);
    }

    /** @inheritDoc */
    public function __invoke(ContextInterface $context)
    {
        $middlewares = $this->beforeMiddlewares;
        $middlewares[] = [$this, 'handle'];
        return co(...$middlewares, ...$this->afterMiddlewares)($context);
    }
}
