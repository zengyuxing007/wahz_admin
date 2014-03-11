<?php

class Action
{
	public function __call($param = '', $param = array())
	{
		$menu = _model('admin_menu')->getList(array(),"ORDER BY `view_order`");
		$info = array();
		foreach ($menu as $v) {
			if($v['parent_id'] == 0) {
				foreach ($menu as $value) {
					if($v['id'] == $value['parent_id']) {
						$v['child'][] = $value;
					}
				}
				$info[] = $v;
			}
		}
		Response::assign('menu', $info);
		Response::display('menu_list.html');
	}

	public function add()
	{
		$result = _model('admin_menu')->getList(array('parent_id' => 0));
		Response::assign('menu_list', $result);
		Response::display('menu_add.html');
	}

	public function edit()
	{
	    $id = Request::Get('id', 0);

		$menu = _model('admin_menu')->read(array('id'=>$id));
		Response::assign('info', $menu);
		$result = _model('admin_menu')->getList(array('parent_id' => 0));
		Response::assign('menu_list', $result);
		Response::display('menu_add.html');
	}

	public function save()
	{
	    $data = Request::Post('menu', array());
	    $id = Request::Post('id', 0);

		if (!empty($id)) {
			_model('admin_menu')->update(array('id'=>$id), $data);
		} else {
			_model('admin_menu')->create($data);
		}
		Response::redirect('menu');
	}

	public function order()
	{
        $id = Request::Get('id', 0);
        /*if (empty($id)) {
            return false;
        }*/
        $menu_main = array();
        if (!empty($id)) {
            $menu_main = _model('admin_menu')->read(array('id'=>$id));
        }
        $menu_result = _model('admin_menu')->getList(array('parent_id'=>$id), "ORDER BY `view_order`");
        Response::assign('mainInfo', $menu_main);
        Response::assign('menuOrder', $menu_result);
        Response::display('menu_order.html');
	}

	public function save_order()
	{
	    $order = Request::Post('order', array());
	    foreach ($order as $key => $v) {
            _model('admin_menu')->update(array('id'=>$key), array('view_order'=>$v));
	    }
	    Response::redirect('menu');
	}

	public function del()
	{
		$id = Request::Get('id', '');
        $id = trim($id, ',');
  
		if(is_int($id)) {
			$result = _model('admin_menu')->delete(array('id'=>$id));
		} elseif (is_string($id)) {
			$newid = explode(',', $id);
			foreach ($newid as $value) {
				$id = (INT)$value;
				$result = _model('admin_menu')->delete(array('id'=>$id));
			}
		}
		if ($result) {
			$res = array('info'=>'ok');
		} else {
			$res = array('info'=>'false');
		}
		header('Content-type: application/json');
    	echo json_encode($res);
	}
}
?>
