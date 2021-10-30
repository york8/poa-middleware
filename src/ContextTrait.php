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

    /**
     * Whether a offset exists
     * @link https://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return bool true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset): bool
    {
        return isset($this->datas[$offset]);
    }

    /**
     * Offset to retrieve
     * @link https://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        return $this->datas[$offset] ?? null;
    }

    /**
     * Offset to set
     * @link https://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->datas[$offset] = $value;
    }

    /**
     * Offset to unset
     * @link https://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->datas[$offset]);
    }

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

    public function __get($name)
    {
        return $this->datas[$name] ?? null;
    }

    public function __set($name, $value)
    {
        $this->datas[$name] = $value;
    }
}
