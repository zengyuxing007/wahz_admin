<?php

class Action
{
    protected $res_type = 11;

    public function __call($param = '', $param=array())
    {
    }


	public function info_view()
    {
	}


    /**
     * 基本资料查询
     *
     */
    public function info()
    {
        $tpl = array(
            'title' => '基本资料查询',
            'desc'  => '',
            'helper' => false //是否显示帮助信息
        );
        Response::assign('tpl', $tpl);

		$unique_id =  Request::get('unique_id', '0');
		$phone_no =  Request::get('phone_no', '0');
		if(!$unique_id && !$phone_no){
			 //msg('错误的查询，未指定查询条件！');
		     Response::display('account_info.html');
			 return;
		}

		$field = "*";

		if($unique_id){
			$where = array('unique_id'=> $unique_id);
		}else if($phone_no){
			$where = array('phone_no'=> $phone_no);
		}
		$info = _model('user_data','wahz')->getField($field,$where);

		if($info){
				$unique_id = $info['unique_id'];
				//get reward	
				$where =  array('uid' => $unique_id);
				$reward = _model('wahz_card','wahz')->getAll('*',$where,'ORDER by getTime');
				Response::assign('reward_info', $reward);
		}
		Response::assign('info',$info);
		Response::display('account_info.html');
	}
}
