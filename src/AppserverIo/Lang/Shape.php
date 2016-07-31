<?php

namespace AppserverIo\Lang;

/**
 * 实现类似hhvm hack中的Shape定义，hack中shape本质上是一个数组
 * 
 * 借助于php的gettype函数，实现类似强类型的概念，不做casting
 * 
 * 支持php的基本类型：int、bool、float、string，及array、object、null
 * 
 * 基本用法:
 * 
 * <pre>
 * class Customer extends Shape
 * {
 *     parent::__construct(
 *          ['id' => self::int, 'name' => self::string, 'categories' => self::array],
 *          $data
 *     );
 * }
 * 
 * //数据访问与数组一样，只是一言不合就会抛异常，确保在开发阶段，做好数据类型分析和转换
 * $customer = new Customer(['id' => 102, 'name' => 'jimmy', 'categories' => [10, 21, 22]]);
 * $customer['id'] = 103; //如果传'103'就会抛异常
 * var_dump($customer['id']);
 * var_dump($customer['categories']);
 * echo count($customer);
 * var_dump($customer->toArray());
 * </pre>
 * 
 * @author jimmy
 * 
 */
class Shape implements \IteratorAggregate, \ArrayAccess, \Countable
{
    
    protected $meta;
    protected $data;
    
    const int = 'integer';
    const string = 'string';
    const float = 'double';
    const bool = 'boolean';
    const array = 'array';
    const object = 'object';
    const null = 'null';
    
    /**
     * 构造方法
     * @param array $meta 元数据定义
     * @param array $data 对应的值
     */
    public function __construct(array $meta, array $data) {
        $this->setMeta($meta);
        $this->setData($data);
    }
    
    /**
     * Returns an iterator for traversing the data.
     * This method is required by the SPL interface [[\IteratorAggregate]].
     * It will be implicitly called when you use `foreach` to traverse the collection.
     * @return \ArrayIterator an iterator for traversing the cookies in the collection.
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->meta);
    }
    
    /**
     * Returns the number of data items.
     * This method is required by Countable interface.
     * @return integer number of data elements.
     */
    public function count()
    {
        return count($this->meta);
    }
    
    /**
     * This method is required by the interface [[\ArrayAccess]].
     * @param mixed $offset the offset to check on
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return $this->keyExists($offset);
    }
    
    /**
     * This method is required by the interface [[\ArrayAccess]].
     * @param integer $offset the offset to retrieve element.
     * @return mixed the element at the offset, null if no element is found at the offset
     */
    public function offsetGet($offset)
    {
        if (!$this->keyExists($offset)) {
            throw new \Exception("$offset not defined.");
        }
        
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }
    
    /**
     * This method is required by the interface [[\ArrayAccess]].
     * @param integer $offset the offset to set element
     * @param mixed $item the element value
     */
    public function offsetSet($offset, $item)
    {
        $this->setValue($offset, $item);
    }
    
    /**
     * This method is required by the interface [[\ArrayAccess]].
     * @param mixed $offset the offset to unset element
     */
    public function offsetUnset($offset)
    {
        throw new \Exception("operation unset is forbidden.");
    }
    
    /**
     * 判断field是否存在
     * @param string $key
     */
    private function keyExists($key) {
        return isset($this->meta[$key]);
    }
    
    /**
     * 给指定field赋值
     * @param string $key
     * @param mixed $value
     * @throws \Exception
     */
    private function setValue($key, $value) {
        if (!$this->keyExists($key)) {
            throw new \Exception("$key not defined.");
        }
        
        if (gettype($value) != $this->meta[$key]) {
            throw new \Exception("type of value $value for $key not matched.");
        }
        
        $this->data[$key] = $value;
    }
    
    protected function allTypes() {
        return [
            self::string,
            self::int,
            self::bool,
            self::float,
            self::array,
            self::object,
            self::null
        ];
    }
    
    /**
     * 构造所支持的类型的默认值
     * @param string $type
     * @throws \Exception
     * @return string|number|boolean|\stdClass
     */
    private function getTypeDefaultValue(string $type) {
        switch ($type) {
            case self::string:
                return '';
                break;
            case self::int:
                return 0;
                break;
            case self::float:
                return 0.00;
                break;
            case self::bool:
                return false;
                break;
            case self::array:
                return [];
                break;
            case self::object;
                return new \stdClass();
                break;
            case self::null;
                return null;
                break;
            default:
                throw new \Exception("default value for $type is not supported.");
                break;
        }
    }
    
    /**
     * 设置meta属性
     * @param array $meta
     * @throws \Exception
     */
    private function setMeta(array $meta) {
        if (!$meta) {
            throw new \Exception("meta is empty.");
        }
        foreach ($meta as $field => $type) {
            if (!in_array($type, $this->allTypes())) {
               throw new \Exception("type $type for $field is not supported.");
            }
        }
        
        $this->meta = $meta;
    }

    /**
     * 给meta对应字段赋值，未定义的字段会被自动过滤
     * @param array $data
     */
    private function setData(array $data) {
        foreach ($this->meta as $field => $type) {
            $this->setValue($field, isset($data[$field]) ? $data[$field] : $this->getTypeDefaultValue($type));
            if (isset($data[$field])) {
                unset($data[$field]);
            }
        }
    
        if ($data) {
            trigger_error('fields:' . implode(',', array_keys($data)) . ' are not defined in the meta.');
        }
    }
    
    /**
     * 输出数组值
     * @return array
     */
    public function toArray() {
        return $this->data;
    }
}
