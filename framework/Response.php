<?php

class Response
{
    private static $html = '';
    //状态相关
    private static $status_code = '200';
    private static $status_msg = 'ok';
    private static $status_url = '';
    
    //模板相关
    private static $tpl= null;


    public static function setView($obj)
    {
        self::$tpl = $obj;
    }

    public static function fetch($file)
    {
        return self::$tpl->fetch($file);
    }

    public static function display($file)
    {
        self::setContent(self::fetch($file));
    }

    public static function assign($tpl_var, $value = null)
    {
        self::$tpl->assign($tpl_var, $value);
    }

    public static function setStatus($code, $msg = '')
    {
        self::$status_code = $code;
        self::$status_msg = $msg;
    }

    public static function set404($msg = 'Not Found')
    {
        self::setStatus(404, $msg);
    }

    public static function redirect($url = null)
    {
        $site = 'http://' . $_SERVER['HTTP_HOST'];
        if (!$url) {
            $url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $site;
        }

        if (substr($url, 0, 4) != 'http') {
            if ($url{0} != '/') {
                $url = '/'.$url;
            }
            $url = $site.$url;
        }

        self::setStatus(302, 'See Other');
        self::$status_url = $url;
    }

    public static function setContent($html)
    {
        self::$html = $html;
    }

    public static function getContent()
    {
        return self::$html;
    }

    public static function flush()
    {
        switch (self::$status_code) {
            case 302:
                header('HTTP/1.1 302 See Other');
                header('Location: '.self::$status_url);
                break;
            case 404:
                header('HTTP/1.1 404 Not Found');
                echo self::$status_msg;
                break;
            default:
                echo self::$html;
                break;
        }
    }
}

?>