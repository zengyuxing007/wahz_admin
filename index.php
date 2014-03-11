<?php
@ini_set('display_errors',        1);
error_reporting(E_ALL);
//error_reporting(0);
define('ROOT_PATH', dirname(__FILE__));

session_set_cookie_params(time()+3600*24*30, '/', $_SERVER['HTTP_HOST']);
require ROOT_PATH . '/framework/init.php';
require ROOT_PATH . '/config/config.php';

Cookie::set_domain('opeapp.setv.sh.cn'); //@todo
define('SITE_URL', 'http://' . $_SERVER['HTTP_HOST']);

session_start();

mb_internal_encoding("UTF-8");
require_once ROOT_PATH . '/lib/common.php';


//if (Request::Get('debug'))	
//define('D_BUG', true);


$view = new Smarty();
$view->template_dir = ROOT_PATH . '/template';
$view->compile_dir = ROOT_PATH . '/data/compile';


Response::setView($view);

Controller::dispatch();

Response::flush();


if(defined('D_BUG') && D_BUG) 
include_once ROOT_PATH . '/framework/Debug.php';

?>

