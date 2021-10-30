# poa-middleware

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Total Downloads][ico-downloads]][link-downloads]

POA框架的中间件组件，使用PHP的Generator实现洋葱圈模型

## 作者

- [York](https://github.com/york8)

## 安装

```json
{
  "require": {
    "poa/middleware": "~0.1"
  }
}
```

```bash
composer update
```

或

```bash
composer install poa/middleware
```

## 使用

```php
use Poa\Middleware\ContextInterface;
use Poa\Middleware\ElapsedTimeMiddleware;
use Poa\Middleware\MiddlewareSystemInterface;
use Poa\Middleware\MiddlewareSystemTrait;
use Poa\Middleware\SimpleContext;

// 基于中间件系统接口实现自己的应用
$app = new class implements MiddlewareSystemInterface {
    use MiddlewareSystemTrait;

    public function handle(ContextInterface $context)
    {
        // 处理业务逻辑
        echo "[Application] handling ...\n";
        print_r($context['route_result']);
    }

    // 启动运行
    public function run()
    {
        $this(new SimpleContext());
    }
};

$app->use(new ElapsedTimeMiddleware())
    ->use(function () {
        // 添加异常处理中间件，捕获运行过程中未被处理的异常
        try {
            echo "[Exception Middleware] begin\n";
            yield;
            echo "[Exception Middleware] end\n";
        } catch (Exception $e) {
            echo "[Exception Middleware] catch: {$e->getMessage()}...\n";
        }
    })
    ->use(function (ContextInterface $context) {
        // 添加路由中间
        echo "[Router Middleware] routing ...\n";
        $context['route_result'] = [
            'c'      => 'ControllerName',
            'a'      => 'actionName',
            'params' => [1, 2, 3]
        ];
        yield;
        echo "[Router Middleware] done\n";
    })
    ->before(function (ContextInterface $context) {
        // 添加日志上报中间件
        echo "[Logger Middleware] begin ...\n";
        yield;
        echo "[Logger Middleware] end ...\n";
        print_r($context->getData('elapsedTime'));
    })
    ->run();
```
运行结果：
```text
[Logger Middleware] begin ...
[Exception Middleware] begin
[Router Middleware] routing ...
[Application] handling ...
Array
(
    [c] => ControllerName
    [a] => actionName
    [params] => Array
        (
            [0] => 1
            [1] => 2
            [2] => 3
        )
)
[Router Middleware] done
[Exception Middleware] end
[Logger Middleware] end ...
Array
(
    [begin_sec] => 1635595772.5639
    [end_sec] => 1635595772.564
    [used_sec] => 4.2915344238281E-5
    [used_msec] => 1
)
```

使用 co2 进行协作

```php
use function Poa\Middleware\co2;

co2(function () {
    foreach (['a', 'b', 'c'] as $v) {
        yield $v;
    }
}, function ($v) {
    return "$v -> foo()";
}, function () {
    while (true) {
        $v = yield;
        yield $v . ' -> bar()';
    }
}, function ($v) {
    echo $v, "\n";
})();
```

结果：

```text
a -> foo() -> bar()
b -> foo() -> bar()
c -> foo() -> bar()
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

[ico-version]: https://img.shields.io/packagist/v/poa/middleware.svg?style=flat-square

[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square

[ico-downloads]: https://img.shields.io/packagist/dt/poa/middleware.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/poa/middleware

[link-downloads]: https://packagist.org/packages/poa/middleware
