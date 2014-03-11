<?php
class Action {
	function l( $type='reward' ) {
		$config_all = Config::get('config_all');
		Response::assign('config_all', $config_all);
		Response::assign('name', '奖品');
		Response::assign('name_mark', $type);
		
		$model_name = 'config_'.$type;
		$rs = _model($model_name)->getList();
		Response::assign('info', $rs);
		
		Response::display ( 'config/l.html' );
	}
	
	function save(){
		$type         = Request::Post('type', '');
		$id           = Request::Post('id', 0);
		$name         = Request::Post('name', '');
		$url = Request::Post('url', '');
		$time         = time();
		
		$data = array(
			'id'          => $id,
			'name'        => $name,
			'reward_img_url'        => $url,
			'record_time' => $time,
		);
		$rs = _model('config_'.$type)->read(array('id'=>$id));
		if ($rs){
			msg('添加失败，id已经存在');
		}
		try {
			_model('config_'.$type)->create($data);
		} catch (Exception $e) {
			msg('添加失败，请重试');
		}
		Response::redirect('/config_all/l/'.$type);
	}
	
	function delete($type='',$id=''){
		if (!$type or !$id) msg('错误');
		
		$model = 'config_'.$type;
		_model($model)->delete(array('id'=>$id));
		msg('删除成功','','success');
	}
}
?>
