<?php

/* web.7k7k.com Session Class by wangdeguo <wangdeguo> */

class Session
{
    public static function start($handler)
    {
        if ($handler instanceof PDO) {
            require_once dirname(__FILE__) . '/Session/Session.PDO.php';
            Session_PDO::start($handler);
        } elseif ($handler instanceof Memcache) {
            require_once dirname(__FILE__) . '/Session/Session.Memcache.php';
            Session_Memcache::start($handler);
        } else {
            throw new Exception("start session exception!");
        }
    }
}
?>