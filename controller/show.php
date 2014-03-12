<?php
class Action {
	function view(){
		
        $tpl = array(
            'title' => '节目安排',
            'desc'  => '',
            'helper' => false //是否显示帮助信息
        );
        Response::assign('tpl', $tpl);

		$model_name = 'show_list';
		$rs = _model($model_name)->getList();
		Response::assign('show_list', $rs);
		
		Response::display ( 'show_list.html' );
	}
	
	function save(){
		$show_time= Request::Get('show_time', '');
		$show_time = strtotime($show_time); 
	    //echo $show_time;exit;
		$show_desc = Request::Get('show_desc', '');
		$now = time();
		
		$data = array(
			'show_time'        => $show_time,
			'`desc`'        => $show_desc,
			'create_time' => $now,
		);
		try {
			_model('show_list')->create($data);
		} catch (Exception $e) {
			msg('添加失败，请重试');
		}
		Response::redirect('/show/view');
	}
	
	function delete($id=''){
		if (!$id) msg('错误');
		
		$model = 'show_list';
		_model($model)->delete(array('id'=>$id));
		msg('删除成功','','success');
	}
}
?>
