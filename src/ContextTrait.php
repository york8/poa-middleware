<?php
/**
 * User: York <lianyupeng1988@126.com>
 * Date: 2021/10/22 16:16
 * @noinspection PhpUnused PhpParameterByRefIsNotUsedAsReferenceInspection
 */

namespace Poa\Middleware;

trait ContextTrait
{
    /** @var array */
    protected array $datas = [];

    public function getData($name)
    {
        return $this->datas[$name] ?? null;
    }

    public function setData($name, $value)
    {
        $this->datas[$name] = $value;
    }

    public function setDatas(array &$datas)
    {
        $this->datas = $datas + $this->datas;
    }
}