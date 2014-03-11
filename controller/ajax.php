<?php

class Action
{
	//获取广告
	function get_advert(){
		$site_id = Request::Get('site_id', 0);
		if (empty($site_id)) exit(json_encode(array('info'=>'error')));
		
		$advert_info =_model('advert')->getList(array('site'=>$site_id));
		$sitepos = _model('sitepos')->getList(array('siteid'=>$site_id));
		foreach ($advert_info as $k=>&$v){
			foreach ($sitepos as $value){
				if ($value['position'] == $v['position'])
				$v['position_name'] = $value['posname'];				
			}
		}
		exit(json_encode(array('info'=>$advert_info)));		
	}
	
	// 根据游戏id 获取 服务器列表
	function get_server(){
		$gid = Request::Get('game_id', 0);
		$rs = _model('servers')->getList(array('gid'=>$gid, 'status'=>1), 'ORDER by datetime DESC');
		exit(json_encode(array('info'=>$rs)));		
	}
}
?>