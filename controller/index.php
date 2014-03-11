<?php

class Action
{
	function __call($param, $param=array()){
		//首页直接做跳转
		Response::redirect('login');
	}
}
?>