<?php

/*  CacheWraper by wangdeguo <wangdeguo> */

//
final class CacheWraper
{
    private $mc = null;
    private static $instance = null;

    private function __construct($mc)
    {
        $this->mc = $mc;
    }

    public static function newInstance($mc)
    {
        self::$instance = new self($mc);
    }

    public static function getInstance()
    {
        return self::$instance;
    }

    /*
        $a->get('get_xxx', 1,2)
        $a->get(array($db,'get_row'), $sql)
        $a->delete(array($db,'get_row'), $sql)
    */
    public function get()
    {
        $args = func_get_args();

        $key = self::gen_key($args);

        if ($this->is_lock($key)) {
            $i = 1;
            while (($result = $this->mc->get($key)) === FALSE && ++$i > 5) {
                // 0.1 second
                usleep(100000);
            }
        } elseif (($result = $this->mc->get($key)) === FALSE) {
            $callback = array_shift($args);

            $this->lock($key);

            if (($result = call_user_func_array($callback, $args)) !== FALSE) {
                $this->mc->set($key, $result, MEMCACHE_COMPRESSED, 60);
            }

            $this->unlock($key);
        }

        return $result;
    }

    public function delete()
    {
        $args = func_get_args();

        $key = self::gen_key($args);

        return $this->mc->delete($key);
    }

    private function is_lock($key)
    {
        $key = 'lock_' . $key;

        if (!$result = $this->mc->get($key)) {
            return false;
        }

        return true;
    }

    private function lock($key)
    {
        $key = 'lock_' . $key;

        return $this->mc->set($key, '1', MEMCACHE_COMPRESSED, 5);
    }

    private function unlock($key)
    {
        $key = 'lock_' . $key;

        return $this->mc->delete($key);
    }

    private static function gen_key($args)
    {
        ob_start();
        var_dump($args);

        return md5(ob_get_clean());
    }
}
?>