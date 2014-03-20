<?php

class Action
{

    public function __call($param = '', $param=array())
    {
    }

    public function data_list($id='#')
    {
			$tpl = array(
							'title' => ($id == '#') ? '用户反馈信息' :'用户反馈信息',
							'desc'  => '',
							'helper' => true, //是否显示帮助信息
					//		'search' => $id == '#' ? true :false
						);
			Response::assign('tpl', $tpl);

			$pager = new Pager(10);
			$advice_list = _model('advice', 'wahz')->getAll('*',array(), 'ORDER by create_time DESC '.$pager->getLimit());
			$count =  _model('advice', 'wahz')->getTotal();

			if ($pager->generate($count)) {
					Response::assign('pager', $pager);
			}
			Response::assign('count', $count);
			Response::assign('pagearray', $pager->getpagesArray(9));
			Response::assign('advice_list', $advice_list);	

			Response::display('advice_list.html');
    }

	function del_all(){

			$id = $_GET['id'];
			$id = trim($id, ',');

			if (is_int($id)) {
					$result = _model('advice', 'wahz')->delete(array('id'=>$id));
			} elseif(is_string($id)) {
					$newid = explode(',', $id);
					foreach ($newid as $value) {
							$result = _model('advice', 'wahz')->delete(array('id'=>$value));
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
}


?>
