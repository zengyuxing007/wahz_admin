<?php

class Action
{

    public function __call($param = '', $param=array())
    {
    }

    public function list_used($id='#')
    {
			$tpl = array(
							'title' => ($id == '#') ? '奖励查询' :'奖励查询',
							'desc'  => '',
							'helper' => true, //是否显示帮助信息
							'search' => $id == '#' ? true :false
						);
			Response::assign('tpl', $tpl);

			$pager = new Pager(10);
			$where = array('is_use' => 1);
			if($id and $id != '#'){
				$where['code'] = $id;
			}
			$reward_list = _model('wahz_card', 'wahz')->getAll('*',$where, 'ORDER by getTime DESC '.$pager->getLimit());
			$count =  _model('wahz_card', 'wahz')->getTotal($where);
			//echo "count:$count";

/*
			$unique_id_list='';
			foreach ($reward_list as $key => $val){
				$unique_id_list[] = $val['uid'];
			}

			$unique_id_list = array_unique($unique_id_list);

			$user_info = _model('user_data','wahz')->getAll('*',array(),'where unique_id in(\''.implode('\',',$unique_id_list).'\')' );

			$user_info_map = array();
			foreach ($user_info as $key => $val){
				$user_info_map[$val['unique_id']] = $val;
			}
*/

			if ($pager->generate($count)) {
					Response::assign('pager', $pager);
			}
			Response::assign('count', $count);
			Response::assign('pagearray', $pager->getpagesArray(9));
			Response::assign('reward_list', $reward_list);	
			//Response::assign('user_info',$user_info_map);

			Response::display('reward_list.html');
    }

	function confirm_all(){

			$id = $_GET['id'];
			$id = trim($id, ',');

		    $confirm = array('is_use' => 2);

			if (is_int($id)) {
					$result = _model('wahz_card', 'wahz')->update(array('id'=>$id),$confirm);
			} elseif(is_string($id)) {
					$newid = explode(',', $id);
					foreach ($newid as $value) {
							$result = _model('wahz_card', 'wahz')->update(array('id'=>$value),$confirm);
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
