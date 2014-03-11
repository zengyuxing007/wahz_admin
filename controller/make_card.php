<?php

class Action
{
    public function __call($param = '', $param=array())
    {
    }


    public function makeCard()
    {
	$times=date('Ymd');
	$invalidTime32 = strtotime(date("Y-m-d 00:00:00", strtotime("+60 day"))); 
	$invalidTime = date('Ymd',$invalidTime32); //60days

	$tpl = array(
	    'title' => '奖励兑换码生成',
	    'desc'  => '',
	    'helper' => true//是否显示帮助信息
	);

	if(!Request::isPost()){
            //get reward config
            $reward = _model('config_reward')->getList(); 
	    Response::assign('tpl',$tpl);
	    Response::assign('times',$times);
	    Response::assign('invalidTime',$invalidTime);
	    Response::assign('config_reward',$reward);
	    Response::display('server/simple_card.html');
	    return;
	}

	if(!Request::Post('makedate','')){
	    msg('生成日期不能为空！','','notice');
	    return;
	}

	$times=Request::Post('makedata',date('Ymd'));
	$invalidTime=Request::Post('invalidTime','2013-01-01');
	$invalidTime32=strtotime($invalidTime);

	$isUse = 0;
	$num=Request::Post('num',0);
	$rewardType =Request::Post('reward',1);
	if($num && $rewardType){
	    $codeData = array(
		'reward_type' => $rewardType,
		'is_use' => $isUse,
		'makeTime' => $times,
		'invalidTime' => $invalidTime32,
	    );
	    $okNum = 0;
	    $antecode='';

	    for($i = 0; $i < $num; $i++){
		$code = strtoupper(random(8));
		$codeData['code'] = $code;
		$antecode .=$code.",";
		if(!_model('wahz_card','wahz')->executesql($codeData))
		{
		    break;
		}
		else
		{
		    ++$okNum;
		}
	    }
	    if($num === $okNum)
	    {
		$antecode = substr($antecode,0,-1);
		
		header('Content-Type: text/plain');
		header('Content-Disposition: attachment; filename="cardCode.txt"');
		//echo "成功生成$nums 个\n";
		echo $antecode;
	    }
	    else
	    {
		Response::assign('error',"生成失败...");
		Response::assign('tpl',$tpl);
		Response::assign('times',$times);
		Response::assign('invalidTime',$invalidTime);
		Response::display('server/simple_card.html');
	    }
	}
    }


}

?>
