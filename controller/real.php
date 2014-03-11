<?php

class Action
{

    function __construct() {

	$date = Request::Get('date', '');
	if (empty($date)){
	    $this->starttime = strtotime("2012-04-27"); 
	    $this->stoptime  = strtotime(date("Y-m-d 23:59:59", strtotime("-1 day")));
	} else {
	    $_arr = explode('|', $date);
	    $this->starttime = strtotime(date("Y-m-d 00:00:00", strtotime($_arr[0])));
	    $this->stoptime  = strtotime(date("Y-m-d 23:59:59", strtotime($_arr[1])));
	}
	Response::assign('starttime', date('Y-m-d', $this->starttime));
	Response::assign('stoptime', date('Y-m-d', $this->stoptime));

    }
    public function __call($param = '', $param=array())
    {
    }

    /**
     * 战车查询
     * 
     */
    public function tank()
    {
	if (empty($_GET['tank_id'])){
	    Response::display('stat/real_tank.html');
	    return ;
	}		

	$tank_id = Request::Get('tank_id', 0);

	$sql = "SELECT count(1) as num from tank where type_id=$tank_id";
	$rs = _shardquery()->query($sql);
	while($tank_num[] = mysql_fetch_assoc($rs)){};

	$info = array(
	    'tank_type_id' => $tank_id,
	    'tank_num' => $tank_num[0]['num'],
	);

	Response::assign('info',$info);
	Response::display('stat/real_tank.html');

    }
    /**
     * 任务查询
     * 
     */
    public function task()
    {
	if(empty($_GET['task_id'])){
	    Response::display('stat/real_task.html');
	    return;
	}
	$task_id = Request::get('task_id', 0);
	if(!$task_id)
	    msg('任务id 识别错误，请返回重新输入');
	//获取当前总人数
	$sql = "SELECT count(1) as num FROM info";
	$reg_rs = _shardquery()->query($sql);
	while($reg_num[] = mysql_fetch_assoc($reg_rs)){};

	//任务完成人数
	$sql = "SELECT COUNT(1) as num FROM task WHERE completed=1  AND id=$task_id";
	$complete_rs = _shardquery()->query($sql);
	while($task_complete_num[] = mysql_fetch_assoc($complete_rs)){};

	//任务领取人数
	$sql = "SELECT COUNT(1) as num FROM task WHERE id=$task_id";
	$claim_rs = _shardquery()->query($sql);
	while($claim[] = mysql_fetch_assoc($claim_rs));

	$info = array(
	    'task_id' => $task_id,
	    'reg_num'  => $reg_num[0]['num'],
	    'task_claim' => $claim[0]['num'],
	    'task_complete' => $task_complete_num[0]['num'],
	    'claim_rate'    => sprintf("%.2f",$claim[0]['num'] / $reg_num[0]['num'] *100),
	    'complete_rate' => sprintf("%.2f",$task_complete_num[0]['num'] / $claim[0]['num'] *100)
	);

	Response::assign('info',$info);

	Response::display('stat/real_task.html');
    }

    /**
     * ACE 排名
     * 
     */
    public function ace_order()
    {
	if(empty($_GET['season_id'])){
	    Response::display('stat/real_ace_order.html');
	    return;
	}
	$season_id = Request::get('season_id', 0);
	if(!$season_id)
	    msg('赛季id 识别错误，请返回重新输入');

	$sql = "SELECT tmp.uid,tmp.nick,m.score,m.win,m.fail,m.last_match_time,m.win/(m.win+m.fail) as win_percent FROM season_match AS m JOIN info tmp ON ( m.uid = tmp.uid ) WHERE m.season_id = $season_id ORDER BY 3 DESC, win_percent DESC,6 DESC,1 LIMIT 500";
	$ace_rs = _shardquery()->query($sql);
	$ace_order = array();
	if(is_resource($ace_rs))
	{
	    while($ace_order[] = mysql_fetch_assoc($ace_rs))
	    {
	    }
	}
	Response::assign('ace_order',$ace_order);

	Response::display('stat/real_ace_order.html');
    }

    /**
     * 挑战
     */
    public function achievement()
    {
	require_once "config/ante_config.php";
	$achi_cfg = Config::get('achievement_config');
	Response::assign('achi_cfg', $achi_cfg);
	if(empty($_GET['achievement_id'])){
	    Response::display('stat/real_achievement.html');
	    return;
	}
	$achievement_id = Request::get('achievement_id', 0);
	if(!$achievement_id)
	    msg('挑战id 识别错误，请返回重新输入');
	Response::assign('achievement_id',$achievement_id);
	//获取当前总人数
	$sql = "SELECT count(1) as num FROM info";
	$reg_rs = _shardquery()->query($sql);
	while($reg_num[] = mysql_fetch_assoc($reg_rs)){};

	$begin = $this->starttime;
	$end = $this->stoptime;

	//挑战完成人数
	$sql = "SELECT COUNT(1) as num FROM achievement WHERE `achievement`=$achievement_id AND `date` BETWEEN $begin AND $end";
	$complete_rs = _shardquery()->query($sql);
	while($achievement_complete_num[] = mysql_fetch_assoc($complete_rs)){};

	$info = array(
	    'achievement_id' => $achievement_id,
	    'reg_num'  => $reg_num[0]['num'],
	    'achievement_complete' => $achievement_complete_num[0]['num'],
	    'complete_rate' => sprintf("%.2f",$achievement_complete_num[0]['num'] * 100 / $reg_num[0]['num'] )
	);
	Response::assign('info',$info);
	Response::display('stat/real_achievement.html');
    }
}
?>
