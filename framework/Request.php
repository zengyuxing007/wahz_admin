<?php
class Request
{
    private static function getValue($value, $default = '')
    {
        if (is_string($default)) {
            return trim($value);
        }

        if (is_int($default)) {
            return intval($value);
        }
        
        if (is_array($default)) {
            return (array)$value;
        }
        
        return floatval($value);
    }

    public function isAjax()
    {
        return 'XMLHttpRequest' == @$_SERVER['X_REQUESTED_WITH'];
    }

    public function isGet()
    {
        return 'GET' == self::getMethod();
    }

    public function isPost()
    {
        return 'POST' == self::getMethod();
    }

    public function isPut()
    {
        return 'PUT' == self::getMethod();
    }

    public function isDelete()
    {
        return 'DELETE' == self::getMethod();
    }

    public function getMethod()
    {
        return @$_SERVER['REQUEST_METHOD'];
    }

    public static function getClienip()
    {
        if (isset($_SERVER['HTTP_CLIENT_IP']))
        {
             $onlineip = $_SERVER['HTTP_CLIENT_IP'];
        }
        elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        {
             $onlineip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        elseif (isset($_SERVER['REMOTE_ADDR']))
        {
             $onlineip = $_SERVER['REMOTE_ADDR'];
        }
        else
        {
            return 'unknown';
        }

        return filter_var($onlineip, FILTER_VALIDATE_IP) !== false ? $onlineip : 'unknown';
    }

    public static function getRf()
    {
        return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    }

    public static function getUa()
    {
        return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    }

    public function getParam($key, $default = '')
    {
        $param = null;

        if (isset($_GET[$key])) {
            $param = $_GET[$key];
        } elseif (isset($_POST[$key])) {
            $param = $_POST[$key];
        }
        // not get the param
        if ($param === null) {
            return $default;
        }

        if (is_string($default))
        {
            return trim($param);
        }

        if (is_int($default))
        {
            return intval($param);
        }

        if (is_array($default))
        {
            return (array)$param;
        }

        return floatval($param);
    }

    public static function Get($key, $default = '')
    {
        if (isset($_GET[$key]))
        {
            return self::getValue($_GET[$key], $default);
        }

        return $default;
    }

    public static function Post($key, $default = '')
    {
        if (isset($_POST[$key]))
        {
            return self::getValue($_POST[$key], $default);
        }

        return $default;
    }

    public function getArgv(array $argv)
    {
        $result = array();
        $last_arg = null;
        foreach ($argv as $val)
        {
            $pre = substr($val, 0, 2);
            if ($pre == '--')
            {
                $parts = explode("=", substr($val, 2), 2);
                if (isset($parts[1]))
                {
                    $result[$parts[0]] = $parts[1];
                }
                else
                {
                    $result[$parts[0]] = true;
                }
            }
            elseif ($pre{0} == '-')
            {
                $string = substr($val, 1);
                $len = strlen($string);
                for ($i = 0; $i < $len; $i++)
                {
                    $key = $string[$i];
                    $result[$key] = true;
                }
                $last_arg = $key;
            }
            elseif ($last_arg !== null)
            {
                $result[$last_arg] = $val;
                $last_arg = null;
            }
        }

        return $result;
    }
}
?>
