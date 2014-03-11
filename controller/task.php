<?php

class Action
{

	function __construct()
	{	//对GET 接收时间进行处理 如果不传值 默认前一天
		$date = Request::Get('date', '');
		if (empty($date)){
			$this->starttime = strtotime(date("Y-m-d 00:00:00", 
				strtotime("-1 day")));
			$this->stoptime  = strtotime(date("Y-m-d 23:59:59", 
				strtotime("-1 day")));
		} else {
			$_arr = explode('|', $date);
			$this->starttime = strtotime(date("Y-m-d 00:00:00", 
				strtotime($_arr[0])));
			$this->stoptime  = strtotime(date("Y-m-d 23:59:59", 
				strtotime($_arr[1])));
		}
		Response::assign('starttime', date('Y-m-d', $this->starttime));
		Response::assign('stoptime', date('Y-m-d', $this->stoptime));

		//初始化sql 生成器
		$this->_sqlbuild = new SqlBuild(true);
		
		//图表展示类型
		$charts_type = Request::Get('charts_type', '');
		$this->charts_type = !$charts_type? 'spline' : $charts_type;
	}


    /**
    * 5号任务完成
    */
    public function five_task($param = '', $param = array())
    {
        $startTime = strtotime(date("Y-m-d 00:00:00", strtotime("-1 day")));
        $sql = "SELECT task.uid,task.complete_time-info.register_time AS cost_time from task JOIN info on ( task.uid = info.uid) where task.id=5 and task.completed=1 and task.complete_time > info.register_time LIMIT 10000;";
        // and task.complete_time > $startTime

        $stmt = _shardquery()->query($sql);

        if($stmt){
            while($row = _shardquery()->my_fetch_assoc($stmt)){
                $rs[] = $row;
            }
        }
        if(!empty($rs)){
            $i = $l = array();
            $x = 1;
            foreach($rs as $k=>$v){
                $x++;
                if($v['cost_time'] /3600 <5)
                    $i[] = array($x,(0+$v['cost_time'])/3600);
                else $l[] = array($x,(0+$v['cost_time'])/3600);
            }
        }

        Response::assign('i', json_encode($i));
        Response::assign('l', json_encode($l));
        Response::display('stat/task_index.html');

    }

	/**
	 * 任务完成人数 @table stat_task_completed_players //No used now
	 *
	 */
	public function task_completed_players()
	{
        require_once "config/ante_config.php";
		$tpl = array(
			'title' => '任务完成人数'
		);

		$tc = Config::get('task_config');

        $taskConfig = array();

        foreach($tc as $k => $v)
        {
            $taskConfig[] = array('id'=>$k,'name' => $v);
        }
		Response::assign('tpl', $tpl);
		Response::assign('taskConfig', $taskConfig);

		//获取数据
		$task_id = Request::get('task_id', 1);

		$where = array(
			'current_day >=' => date('Ymd',$this->starttime),
			'current_day <=' => date('Ymd',$this->stoptime)
		);

		$rs = _model('stat_task_completed_players')->getAll("task_$task_id as task,current_day",$where);
		Response::assign('stat_task_completed_players', $rs);
		
		//图表数据组装
		$y_cate = $serie = array();
		foreach($rs as $key=>$val)
		{
			$y_cate[] = $val['current_day'];
            $serie['completed'][] = 0+$val["task"];	
        }

        //图表组装
        if(isset($serie['completed'])){
            $charts = new Highcharts('charts_task_completed');
            $_charts = $charts->set_type($this->charts_type)->set_dimensions(800,300)->set_title("任务[$task_id]完成人数")
                //设置x坐标数值， 反向显示数据  坐标值旋转30度显示
                ->set_Axis(array('categories'=>$y_cate, 'labels'=>array('rotation'=>30)), 'x')
                ->set_Axis(array('title'=>array('text'=>'人数')))
                ->set_serie(array('data'=>$serie['completed'], 'name'=>'完成人数'))
                ->render();
            Response::assign('charts_task_completed',$_charts);
        }		

        Response::display("stat/task_completed_players.html");

	}

	/**
	 * 任务领取人数
	 * @table stat_task_get_players
	 *
	 */
	public function task_get_players()
	{
        require_once "config/ante_config.php";

		$tpl = array(
			'title' => '任务相关'
		);


		$tc = Config::get('task_config');

        $taskConfig = array();

        foreach($tc as $k => $v)
        {
            $taskConfig[] = array('id'=>$k,'name' => $v);
        }
		Response::assign('tpl', $tpl);
		Response::assign('taskConfig', $taskConfig);

		//获取数据
		$task_id = Request::get('task_id', 1);
		Response::assign('task_id', $task_id);

		$where = array(
			'current_day >=' => date('Ymd',$this->starttime),
			'current_day <=' => date('Ymd',$this->stoptime)
		);

		$task_get = _model('stat_task_get_players')->getAll("task_$task_id as uncompleted,current_day",$where);
		$task_completed = _model('stat_task_completed_players')->getAll("task_$task_id as completed,current_day",$where);
        $task_allCompleted = _model('stat_task_all_completed_players')->getAll("task_$task_id as all_completed,current_day",$where);

        //获取总注册数
        $sql = $this->_sqlbuild->select('current_day,role_register_total_num')->from('stat_register')
            ->where(array('current_day >= '=> date('Ymd',$this->starttime), 'current_day <= '=> date('Ymd',$this->stoptime)))
            ->_compile_select();
        $reg_rs = _model('stat_register')->runsql($sql);
        $_reg_rs = '';
        foreach ($reg_rs as $k=>$v){
            $_reg_rs[$v['current_day']] = $v['role_register_total_num'];
        }
				
		foreach($task_get as $k=>&$v){
			foreach($task_completed as $key=>$val){
				if($v['current_day'] == $val['current_day'])
					$v['completed'] = $val['completed'];
			}

			foreach($task_allCompleted as $key=>$val){
				if($v['current_day'] == $val['current_day'])
					$v['all_completed'] = $val['all_completed'];
			}

			if (!empty($_reg_rs[$v['current_day']]['role_register_num'])){
				$v['all_completed_rate'] = sprintf("%.2f", $v['all_completed'] * 100 /$_reg_rs[$v['current_day']]);
			}
		}
	
		Response::assign('stat_task_get_players', $task_get);
		
		
		//图表数据组装
		$y_cate = $serie = array();
		foreach($task_get as $key=>$val)
		{
			$y_cate[] = $val['current_day'];
			$serie[] = 0+$val["uncompleted"];
		}

				//图表组装
			/*
		if(isset($serie['get'])){
			$charts = new Highcharts('charts_task_get');
			$_charts = $charts->set_type($this->charts_type)->set_dimensions(800,300)->set_title("任务[$task_id]领取人数")
			//设置x坐标数值， 反向显示数据  坐标值旋转30度显示
			->set_Axis(array('categories'=>$y_cate, 'labels'=>array('rotation'=>30)), 'x')
			->set_Axis(array('title'=>array('text'=>'人数')))
			->set_serie(array('data'=>$serie, 'name'=>'领取未完成人数'))
			->render();
			Response::assign('charts_task_get',$_charts);
		}	
		*/

		Response::display("stat/task_get_players.html");
	}
}
?>
