<?php

class Action
{
	function __construct(){
		header('Content-Type: text/html; charset=UTF-8');
	}
	public function __call($param = '',$param=array())
	{
		if (empty($_SESSION)) {
			Response::display("login.html");
		} else {
			Response::display("welcome.html");
		} 		
	}

	function p(){
		Response::display('test_test.html');
	}

	
	public function save()
	{
		if (!isset($_POST)) die("来源错误，<a href='javascript:history.back()'>点击返回</a>");
		
		$username = Request::Post('username', '');
		$pw = Request::Post('password', '');
		if (empty($username) || empty($pw)) {
			die( "账号/密码不能为空,<a href='javascript:history.back()'>点击返回</a>" );
		}
		$pw = md5($pw);
		$user_info = _model("user")->read(array("user_name"=>$username, "password"=>$pw));
		
		if (empty($user_info)) die( "账号/密码错误,<a href='javascript:history.back()'>点击返回</a>" );

		$role = _model("user_role")->read(array("id"=>$user_info['role_id']));
		
        $user_info["privileges"] = explode(",", $role['privilege']);
          
		if (empty($role['privilege'])) 	die( "对不起，你没有权限登录后台,<a href='javascript:history.back()</a>'" );
			
		unset($role['password']);
		$_SESSION = $user_info;

		Response::redirect('login');
	}
	
	public function logout()
	{
		session_destroy();
		Response::display("login.html");
	}
	
}
?>
