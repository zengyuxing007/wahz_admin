<?php 
class Time {
    //前天开始时间
    static function getTwoDaysBeforeStartTime($type = '')
    {
		$timestamp = strtotime(date("Y-m-d 00:00:00", strtotime("-2 day"))); 		
		if ($type) 	return date($type, $timestamp);
		else return $timestamp;
    }

	//前天结束时间
	static function getTwoDaysBeforeStopTime($type = ''){
		$timestamp = strtotime(date('Y-m-d 23:59:59', strtotime("-2 day")));
		if($type) return date($type, $timestamp);
		else return $timestamp;
	}

	//昨天开始时间
	static function getLastDayStartTime($type = ''){
		$timestamp = strtotime(date("Y-m-d 00:00:00", strtotime("-1 day"))); 		
		if ($type) 	return date($type, $timestamp);
		else return $timestamp;
	}
	//昨天结束时间
	static function getLastDayStopTime($type = ''){
		$timestamp = strtotime(date('Y-m-d 23:59:59', strtotime("-1 day")));
		if($type) return date($type, $timestamp);
		else return $timestamp;
	}
	//今天开始时间
	static function getTodayStartTime($type = ''){
		$timestamp = strtotime(date('Y-m-d 00:00:00'), time());
		if ($type) return date($type, $timestamp);
		else return $timestamp;
	}
	static function getTodayStopTime($type = ''){
		$timestamp = strtotime(date('Y-m-d 23:59:59'), time());
		if ($type) return date($type, $timestamp);
		else return $timestamp;
	}
	
	/**
	 * 指定时间 上周开始时间
	 * @param $type  返回格式化参数，empty 为 返回时间戳
	 * @param $day   输入时间，empty 为当前时间  时间戳
	 */
	static function getLastWeekStartDay($type='', $timestamp=''){
		if (empty($timestamp)) $timestamp = time();
		$monday = mktime(0,0, 0,date("m",$timestamp),date("d",$timestamp)-date("N",$timestamp)+1-7,date("Y",$timestamp)); 
		
		if ($type)return date($type,$monday);
		else return $timestamp;
	}
	
	static function  getLastWeekEndDay($type='',$timestamp=''){
		if (empty($timestamp)) $timestamp = time();
		$firstday = date("N",$timestamp);
		
		$sunday = mktime(23,59,59,date("m",$timestamp),date("d",$timestamp)-date("N",$timestamp)+7-7,date("Y",$timestamp));
		
		if ($type)return date($type,$sunday);
		else return $timestamp;
	}

	static function getThisWeekStartDay($type='', $timestamp=''){
		if (empty($timestamp)) $timestamp = time();
		$monday = mktime(0,0, 0,date("m",$timestamp),date("d",$timestamp)-date("N",$timestamp)+1,date("Y",$timestamp)); 
		
		if ($type)return date($type,$monday);
		else return $timestamp;
	}

	static function  getThisWeekEndDay($type='',$timestamp=''){
		if (empty($timestamp)) $timestamp = time();
		$firstday = date("N",$timestamp);
		
		$sunday = mktime(23,59,59,date("m",$timestamp),date("d",$timestamp)-date("N",$timestamp)+7,date("Y",$timestamp));
		
		if ($type)return date($type,$sunday);
		else return $timestamp;
	}
	
}
?>
