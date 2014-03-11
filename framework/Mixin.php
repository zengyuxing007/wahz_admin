<?php

/*  Mixin by wangdeguo <wangdeguo> */

class Mixin
{
    private $args = array();

    function __construct()
    {
        $this->args = func_get_args();
    }

    function __call($name, $arguments)
    {
        foreach ($this->args as $obj)
        {
            if (is_callable(array($obj, $name)))
            {
                return call_user_func_array(array($obj, $name), $arguments);
            }
        }

        throw new Exception("can't call method:$name");
    }

    function __get($key)
    {
        foreach ($this->args as $obj)
        {
            if (array_key_exists($key, get_object_vars($obj)))
            {
                return $obj->$key;
            }
        }

        return null;
    }

    function __set($key, $value)
    {
        foreach ($this->args as $obj)
        {
            if (array_key_exists($key, get_object_vars($obj)))
            {
                return $obj->$key = $value;
            }
        }

        return null;
    }
}
?>