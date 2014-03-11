<?php
Lite::init();

class Lite
{
    const Version = '20111108';

    public static function init()
    {
        spl_autoload_register(array(__CLASS__, 'lite_autoload'));

        date_default_timezone_set('Etc/GMT-8');
        set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__));

        if (get_magic_quotes_gpc())
        {
            $_POST = self::stripslashes_recursive($_POST);
            $_GET = self::stripslashes_recursive($_GET);
            $_COOKIE = self::stripslashes_recursive($_COOKIE);
        }
    }

    public static function stripslashes_recursive($array)
    {
        $array = is_array($array) ? array_map(array(__CLASS__, 'stripslashes_recursive'), $array) : stripslashes($array);

        return $array;
    }

    public static function lite_autoload($classname)
    {
        $dir = dirname(__FILE__);
        switch ($classname)
        {
        	case 'ShardQuery':
        		set_include_path(get_include_path() . PATH_SEPARATOR . $dir . '/3rd/ShardQuery/');
				require_once $dir .'/3rd/ShardQuery/include/shard-query.php';
				require_once $dir .'/3rd/ShardQuery/include/common.php';
        		break;
            case 'Smarty':
                require_once $dir . '/3rd/Smarty/libs/Smarty.class.php';
                break;
            case 'JavaScriptPacker':
                require_once $dir . '/3rd/JavaScriptPacker/class.JavaScriptPacker.php';
                break;
            case 'getid3':
                require_once $dir . '/3rd/getid3/getid3.php';
                break;
            case 'MSN':
                require_once $dir . '/3rd/phpmsnclass_1.9/msn.class.php';
                break;
            case 'PHPMailer':
                require_once $dir . '/3rd/phpMailer_v2.3/class.phpmailer.php';
                break;
            case 'HTMLPurifier_Config':
                require $dir . '/3rd/htmlpurifier/library/HTMLPurifier.safe-includes.php';
                require $dir . '/3rd/htmlpurifier/library/HTMLPurifier.func.php';
                break;
            default:
            	if (is_file($dir . '/' . $classname . '.php')){
            		require_once $dir . '/' . $classname . '.php';
            	}

                break;
        }
    }
}


function getInstance()
{
    static $obj = array();

    $args = func_get_args();
    $class = array_shift($args);

    ob_start();
    var_dump($args);
    $key = ob_get_clean();
    
    $key = sha1($key);
	
    if (!isset($obj[$class][$key]))
    {
        $reflection = new ReflectionClass($class);
        $obj[$class][$key] = call_user_func_array(array($reflection, 'newInstance'), $args);
    }

    return $obj[$class][$key];
}

function gen_pass($string)
{
    isset($_SERVER['HTTP_USER_AGENT']) || $_SERVER['HTTP_USER_AGENT'] = __FUNCTION__;
    $key     = strtr($_SERVER['HTTP_USER_AGENT'] . __FILE__, ' ', '');
    $key_len = strlen($key);
    $str_len = strlen($string);

    $code = '';
    for ($i = 0; $i < $str_len; ++$i)
    {
        $k     = $i % $key_len;
        $code .= $string[$i] ^ $key[$k];
    }

    return $code;
}

function only_chinese_and_word($string)
{
    $chinese = "(?:[".chr(228)."-".chr(233)."][".chr(128)."-".chr(191)."][".chr(128)."-".chr(191)."])";
    $string = preg_replace("/$chinese/", '', $string);
    return !preg_match("/\W/", $string);
}

?>
