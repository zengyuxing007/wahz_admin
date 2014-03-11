<?php
abstract class Controller
{
    public $view = null;
    protected $config = array();
    protected $allow = array();
    protected $method = null;
    private static $self = null;

    function __construct($method, $view)
    {
        $this->view = $view;

        $this->method = $method;

        // 载入初始化函数
        if (method_exists($this, 'init'))
        {
            $this->init();
        }
    }
    
 	public static function dispatch($path = './controller')
    {
    	 //@todo fix
        $kk_num = Request::Get('kk_num',0); 
        if ($kk_num) Cookie::set('stat_kk_num', $kk_num); 
        Response::assign('stat_kk_num', Cookie::get('stat_kk_num'));
        
        $url = trim(@$_GET['url'], ' /');
        
        list($url) = explode('.', $url, 2);
        $tmp = explode('/', $url);

        $seq = 0;
        $count = count($tmp);
        for ($i = 0; $i < $count; $i++) {
            if (!is_dir($path.'/'.$tmp[$i])) {
                break;
            }
            $path .= '/'.$tmp[$i];
            $seq++;
        }
		

        if ($seq > 0) {
            $tmp = array_slice($tmp, $seq);
        }

        $controller = array_shift($tmp);
        !$controller && $controller = 'index';
        $action = array_shift($tmp);
        !$action && $action = 'index';
        $file = $path.'/'.$controller.'.php';
        
        /* 后台权限控制 start */
        //判断每个页面的访问权限
		if (!check_auth($controller, $action)) {
			msg('权限不足', '', 'error');
		}

		$privileges = isset($_SESSION['privileges']) ? $_SESSION['privileges'] : array();
        Response::assign("privileges", $privileges);

		$side_menu = _model("admin_menu")->getList(array('is_show'=>1), "ORDER BY `view_order`");
		Response::assign("side_menu", $side_menu);
		/* 后台权限控制 stop */

        if (!file_exists($file)) {
            $action = $controller;
            $controller = 'index';
            $file = $path.'/'.$controller.'.php';
        }

        require $file;
        self::$self = new Action($controller, $action, $tmp);
        
        Response::assign('controller', $controller);
        Response::assign('action', $action);
	
        return call_user_func_array(array(self::$self, $action), $tmp);
    }

    // 方便的view调用
    protected function display($file)
    {
        // ob_start("ob_gzhandler");
        $this->view->display($file);
        // ob_end_flush();
    }

    // 方便的view调用
    protected function assign($tpl_var, $value = null)
    {
        $this->view->assign($tpl_var, $value);
    }

    // 方便的view调用
    protected function fetch($file)
    {
        return $this->view->fetch($file);
    }

    protected function redirect($url = '')
    {
        $site = 'http://' . $_SERVER['HTTP_HOST'];
        if (!$url)
        {
            $url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $site;
        }

        if (substr($url, 0, 4) != 'http') {
            if ($url{0} != '/') {
                $url = '/'.$url;
            }
            $url = $site.$url;
        }
        header('Location: ' . $url);
        exit;
    }
}

?>
