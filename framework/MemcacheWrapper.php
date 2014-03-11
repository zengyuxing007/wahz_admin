<?php
class MemcacheWrapper
{
    private $mc = null;
    private static $instance = null;
    private $ns = '';

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

    public function NameSpace($key)
    {
        $this->ns = $key;

        return $this;
    }

    public function delete($key = null, $timeout = 0)
    {
        if ($this->ns) {
            if ($key === null) {
                $this->mc->increment($this->ns, 1);
            } else {
                $ns = $this->mc->get($this->ns);
            }
            $this->ns = '';
            if (empty($ns)) {
                return true;
            }
        } else {
            $ns = '';
        }

        return $this->mc->delete($ns.$key, $timeout);
    }

    public function get($key)
    {
        if ($this->ns) {
            $ns = $this->mc->get($this->ns);
            $this->ns = '';
            if ($ns === false) {
                return false;
            }
        } else {
            $ns = '';
        }

        return $this->mc->get($ns.$key);
    }

    public function set($key, $value, $expire = 60)
    {
        if ($this->ns) {
            $ns = $this->mc->get($this->ns);
            $ns === false && $ns = time();
            // notice the ns should not use MEMCACHE_COMPRESSED
            $this->mc->set($this->ns, $ns, 0, 3600*24);
            $this->ns = '';
        } else {
            $ns = '';
        }

        return $this->mc->set($ns.$key, $value, MEMCACHE_COMPRESSED, $expire);
    }
}

?>