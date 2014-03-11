<?php

class Action
{
    public function __call($param = '', $param=array())
    {
	$pager = new Pager(14);
	$user_list = _model('user')->getList(array(),'ORDER BY `add_time` DESC '.$pager->getLimit());
	$user_count = _model('user')->getTotal();
	if ($pager->generate($user_count)) {
	    Response::assign('pager', $pager);
	}
	Response::assign('count', $user_count);
	Response::assign('pagearray', $pager->getpagesArray(9));
	Response::assign('user', $user_list);

	$this->get_role();

	Response::display('user_list.html');
    }

    public function search()
    {
	$keyword = "%".Request::Get('keyword','')."%";
	$role_id = Request::Get('role_id', 0);
	$pager = new Pager(14);

	if ($keyword) {
	    $user_list = _model('user')->getList(array('role_id'=>$role_id, 'user_name'=>$keyword), $pager->getLimit());
	    $user_count = _model('user')->getTotal(array('role_id'=>$role_id, 'user_name'=>$keyword));
	} else {
	    $user_list = _model('user')->getList(array('role_id'=>$role_id), $pager->getLimit());
	    $user_count = _model('user')->getTotal(array('role_id'=>$role_id));
	}
	if ($pager->generate($user_count)) {
	    Response::assign('pager', $pager);
	}
	Response::assign('count', $user_count);
	Response::assign('pagearray', $pager->getPagesArray(9));
	Response::assign('user', $user_list);

	$this->get_role();

	Response::display('user_list.html');
    }

    public function user_role()
    {
	$value = Request::Get('value', 0);
	$keyword = Request::Get('key', '');
	if (!empty($value) && !empty($keyword)) {
	    $pager = new Pager(14);
	    $user_list = _model('user')->getList(array('role_id' =>$value), $pager->getLimit());
	    $user_count = _model('user')->getTotal(array('role_id'=>$value));
	    if ($pager->generate($user_count)) {
		Response::assign('pager', $pager);
	    }
	    $role_result = _model('user_role')->read($value);
	    foreach ($user_list as $key => $v) {
		$user_list[$key]['role_name'] = $role_result['name'];
	    }
	    Response::assign('user', $user_list);
	    Response::assign('pagearray', $pager->getPagesArray(9));
	    Response::assign('count', $user_count);

	    $this->get_role();

	    Response::display('user_list.html');
	}
    }

    public function add()
    {
	$this->get_role();
	Response::display('user_add.html');
    }

    public function user_settings()
    {
	$id = $_SESSION['id'];

	// 主表信息
	$user_info = _model('user')->read(array('id'=>$id));
	Response::assign('info', $user_info);

	Response::display('user_settings.html');
    }


    public function edit()
    {
	$id = Request::Get('id', 0);

	$user_info = _model('user')->read(array('id'=>$id));

	$role_result = _model('user_role')->getList();
	Response::assign('role', $role_result);

	Response::assign('info', $user_info);
	Response::display('user_add.html');
    }

    public function save()
    {

	if (empty($_GET['method'])) {
	    if (!empty($_POST['user_name'])) {
		$data['user_name'] = Request::Post('user_name', '');
	    } else {
		msg('用户名不能为空');
	    }
	}
	$data['email'] = Request::Post('email', '');
	$data['mobile'] = Request::Post('mobile', '');

	//判断用户权限
	if (!empty($_POST['role_id'])) {
	    $data['role_id'] = Request::Post('role_id', 0);
	}

	//如果是创建用户
	if (empty($_POST['id'])) {
	    $check_username = _model('user')->getList(array('user_name'=>$data['user_name']));
	    if ($check_username) {
		msg('用户名已经存在');
	    }
	    $data['password'] = md5(Request::Post('password', ''));
	    $data['hash'] = $this->hash();

	    $data = $data + Request::Post('attribute', array());

	    $user_id = _model('user')->create($data);
	    //如果是更新用户
	} else {
	    //$_GET['method']为空则为管理员编辑用户,否则则为用户自己编辑
	    //判断用户名是否存在
	    if (empty($_GET['method'])) {
		$check_username = _model('user')->read(array('user_name'=>$data['user_name']));
		if ($check_username['user_name'] != $data['user_name']) {
		    msg('用户名已经存在');
		}
	    }

	    if(!empty($_POST['pwd'])) {
		$data['password'] = md5(Request::Post('pwd', ''));
	    }
	    _model('user')->update(array('id'=>$_POST['id']), $data + Request::Post('attribute', array()));
	}

	Response::redirect('user/list');
    }

    public function del()
    {
	$id = $_GET['id'];
	$id = trim($id, ',');

	if (is_int($id)) {
	    $result = _model('user')->delete(array('id'=>$id));
	} elseif(is_string($id)) {
	    $newid = explode(',', $id);
	    foreach ($newid as $value) {
		$result = _model('user')->delete(array('id'=>$value));
	    }
	}
	if($result) {
	    $res = array('info'=>'ok');
	} else {
	    $res = array('info'=>'false');
	}
	header('Content-type: application/json');
	echo json_encode($res);
    }

    /**
     * 公共方法
     */
    public function get_role()
    {
	$result = _model('user_role')->getList();
	Response::assign('role',$result);
    }

    private function hash()
    {
	$str = 'ybo69';
	$chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
	$chararr = str_split($chars);
	shuffle($chararr);
	$chars = implode($chararr);
	$i = 0;
	while ($i<5) {
	    $i++;
	    $str .= $chars[rand(0, 10)];
	}
	$newstr = str_split($str);
	shuffle($newstr);
	return $news = implode('', $newstr);
    }
}
?>
