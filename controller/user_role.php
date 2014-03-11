<?php

class Action
{
	protected $res_type = 11;

	public function __call($param = '', $param=array())
	{
		$result = _model('user_role')->getList();
		foreach($result as $k =>  $v)
		{
			$result[$k]['num'] = _model('user')->getTotal(array('role_id'=>$v['id']));
		}
		Response::assign('role', $result);
		Response::display('role_list.html');
	}

	public function add()
	{
		$role = _model('admin_menu')->getList();
		Response::assign('role', $role);
		Response::display('role_add.html');
	}

	public function edit()
	{
	    $id = Request::Get('id', 0);

		if (!empty($id)) {
			$result = _model('user_role')->read(array('id'=>$id));
			Response::assign('info', $result);
			$role = _model('admin_menu')->getList();
			Response::assign('role', $role);
			$roleArray = explode(',', $result['privilege']);
			Response::assign('roleArray', $roleArray);
			Response::display('role_add.html');
		} else {
			return array("您的来路不明,请返回重试", "error");
		}
	}

	public function save()
	{
	    $data = array();
		$data['name'] = Request::Post('name', '');
		$data['type'] = Request::Post('type', '');
		$privilege = Request::Post('privilege', array());
		$id = Request::Post('id', 0);
		if (!empty($privilege))
		{
			$data['privilege'] = implode(',', $privilege);
		} else {
			$data['privilege'] = "";
		}
		if (empty($id)) {
			_model('user_role')->create($data);
		} elseif(!empty($id)) {
			_model('user_role')->update(array('id'=>$id), $data);
		}
		Response::redirect('user_role');
	}
}

?>