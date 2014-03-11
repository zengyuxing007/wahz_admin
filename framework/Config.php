<?php 
abstract class Bag
{
    private static $bag = array();
    protected static $ns = '';

    protected static function _set($key, $value = '')
    {
        if (!is_array($key)) {
            $key = array($key=>$value);
        }

        foreach ($key as $k => $v) {
            self::$bag[self::$ns.$k] = $v;
        }

        return true;
    }

    protected static function _get($key)
    {
        if (func_num_args() > 1) {
            $key = func_get_args();
        }

        if (is_array($key)) {
            $out = array();
            foreach ($key as $key => $val) {
                $out[] = @self::$bag[self::$ns.$key];
            }

            return $out;
        }

        return @self::$bag[self::$ns.$key];
    }

    protected static function _remove($key)
    {
        if (isset(self::$bag[self::$ns.$key])) {
            unset(self::$bag[self::$ns.$key]);
        }

        return true;
    }
}

class Config extends Bag
{
	//@todo 待定
	public static function loadfile()
	{
		
	}

    public static function set()
    {
        parent::$ns = __CLASS__;
        $params = func_get_args();
        return call_user_func_array(array('parent','_set'), $params);
    }

    public static function get()
    {
        parent::$ns = __CLASS__;
        $params = func_get_args();
        return call_user_func_array(array('parent','_get'), $params);
    }
}
?>