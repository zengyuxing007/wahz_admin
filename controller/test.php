<?php

class Action
{
	function __construct() {
		//对GET 接收时间进行处理 如果不传值 默认前一天
		$date = Request::Get('date', '');
		if (empty($date)){
			$this->starttime = strtotime(date("Y-m-d 00:00:00", strtotime("-1 day")));
			$this->stoptime  = strtotime(date("Y-m-d 23:59:59", strtotime("-1 day")));
		} else {
			$_arr = explode('|', $date);
			$this->starttime = strtotime(date("Y-m-d 00:00:00", strtotime($_arr[0])));
			$this->stoptime  = strtotime(date("Y-m-d 23:59:59", strtotime($_arr[1])));
		}
		Response::assign('starttime', date('Y-m-d', $this->starttime));
		Response::assign('stoptime', date('Y-m-d', $this->stoptime));

		/*
		$time = '2012/01/02';
		$rs1 = Time::getLastWeekStartDay('Ymd',strtotime($time));
		$rs2 = Time::getLastWeekEndDay('Ymd',strtotime($time));
		echo '测试时间' . $time . '--'. $rs1.'--'.$rs2;
		var_dump($rs1,$rs2);
		*/
		//初始化sql 生成器
		$this->_sqlbuild = new SqlBuild(true);
		
		//图表展示类型
		$this->charts_type = !Request::Get('charts_type', '') ? 'spline' : Request::Get('charts_type','');
	}
	
	//test
	function t(){
		$where = array(
			'current_day >= '=> date('Ymd',$this->starttime), 
			'current_day <= '=> date('Ymd',$this->stoptime)
		);
		$rs = _model('stat_act')->getAll('*', $where, 'ORDER BY current_day DESC');
		
		Response::assign('stat_act',$rs);	
			
		//数据组装
		$y_cate = $serie = array();
		foreach($rs as $key=>$val){
			$y_cate[] = $val['current_day'];
			/*
			$serie['three_act_num'][] = 0+$val['three_act_num'];
			$serie['week_act_num'][] = 0+$val['week_act_num'];
			$serie['two_week_act_num'][] = 0+$val['two_week_act_num'];
			*/
			$serie['three_act_num'][] = array((0+strtotime($val['current_day']))*1000,0+$val['three_act_num']); 
			$serie['week_act_num'][] = array((0+strtotime($val['current_day']))*1000,0+$val['week_act_num']); 
			$serie['two_week_act_num'][] = array((0+strtotime($val['current_day']))*1000,0+$val['two_week_act_num']); 
		}	

		if(isset($serie['three_act_num'])){
			$charts = new Highcharts('charts_act');
			$_charts = $charts->set_type($this->charts_type)
				->set_dimensions(800,300)
				->set_title('活跃用户统计')
				//->set_Axis(array('labels'=>array('rotation'=>30)), 'x')
				->set_Axis(array('title'=>array('text'=>'人数(个)')))
				->set_serie(array('data'=>$serie['three_act_num'], 'name'=>'三日活跃人数'))
				->set_serie(array('data'=>$serie['week_act_num'], 'name'=>'周活跃用户数'))
				->set_serie(array('data'=>$serie['two_week_act_num'], 'name'=>'双周活跃用户数'))
				->set_data('tooltip', 
					array('formatter'=>"function(){var value=this.value;+"chartsformat()"+}")
				)	
				->set_Axis(array( 
					'type'=>'datetime',
					'tickInterval'=>60*60*24*5*1000 ,
					'labels'=>array(
						'rotation' =>30,
						'formatter'=>"function(){var vDate=new Date(this.value);return vDate.getMonth()+1+'月'+vDate.getDate()+'日  ';}"
						)
					),'x')
				->render();
			Response::assign('charts_act', $_charts);	
		}
		Response::display('stat/stat_act.html');
		Response::display('stat/stat_active.html');		
	
	}
	
	function test(){
		function ftok($pathname, $proj_id) {   
			$st = @stat($pathname);  
			var_dump($st);
			var_dump($st['dev'] & 0xff << 16);
			
			if (!$st) {   
			   return -1;   
			 }   
			$key = sprintf("%u", (($st['ino'] & 0xffff) | (($st['dev'] & 0xff) << 16) | (($proj_id & 0xff) << 24)));   
			return $key;   
		} 
		$shm_key = ftok(__FILE__, 't'); 
		var_dump($shm_key);

 
		
		$rs1 = _model('user')->getList();
		$rs2 =_model('user')->getList();
		$rs3 =_model('user')->getList();
		$rs4 =_model('user')->getList();
		//var_dump($rs1,$rs2,$rs3,$rs4);

		Response::display("test_test.html");
	
	}

	function c(){
		$shardmem = new SharedMemory();
		var_dump($shardmem->getinfo());
		$val = $shardmem->get(64);
		shmop_delete(16);
		shmop_close(16);
		$shardmem->close();
		var_dump($val);


	}
	function b(){
		//读100字节共享内存空间
		$shm_id = shmop_open(0xff3, "a", 0644, 100);

		//获取共享内存空间中的前11个字节的内容
		//create.php中 $super 变量长度为11
		$share = shmop_read($shm_id, 0, 11);
		
		shmop_delete(15);
		echo $shm_id,$share;

		//关闭
		shmop_close($shm_id);
	}

	function a(){
			//定义全局变量
		$super = '这里是i一个数组空间里空间里空间里看jkljkljlkjjkljlkjlkj交换空间化接口及刻录机蓝景丽家金荔科技离开';
		$shm_id = shmop_open(0xff3, "c", 0644, 100);
		if (!$shm_id)	{
		 echo "申请空间失败<br>";
		}

		//内容写入共享内存空间
		if (shmop_write($shm_id, $super, 0)){
		 echo "全局变量已经写入共享内存<br>";
		}else{
		 echo "写入共享内存失败<br>";
		}

		//关闭共享内存空间
		shmop_close($shm_id);
	}


}
?>
