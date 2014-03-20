<?php

function _shardquery(){
	static $_obj = array();
	
	$g_shards = Config::get('g_shards');
	$g_query_params = Config::get('g_gearman');	
	$key = md5($g_shards.$g_query_params);
	if(!isset($_obj[$key])) {
		$_obj[$key] = new ShardQuery($g_shards, $g_query_params); 
	}
	return $_obj[$key];
}

function _model($name,$database = '')
{
    static $models = array();
    $key = sha1($name . $database);

    if (!isset($models[$key])) {
        $class = $name . '_model';
        require_once ROOT_PATH . "/model/$name.php";
        
        $models[$key] = new $class(getInstance( 'Db',$database));
    }

    return $models[$key];
}

function trim_right($str)
{
    $len = strlen($str);
    /* 为空或单个字符直接返回 */
    if ($len == 0 || ord($str{$len - 1}) < 127) {
        return $str;
    }
    /* 有前导字符的直接把前导字符去掉 */
    if (ord($str{$len - 1}) >= 192) {
       return substr($str, 0, $len - 1);
    }
    /* 有非独立的字符，先把非独立字符去掉，再验证非独立的字符是不是一个完整的字，不是连原来前导字符也截取掉 */
    $r_len = strlen(rtrim($str, "\x80..\xBF"));
    if ($r_len == 0 || ord($str{$r_len - 1}) < 127) {
        return sub_str($str, 0, $r_len);
    }

    $as_num = ord( ~ $str{$r_len - 1});
    if ($as_num > (1 << (6 + $r_len - $len))) {
        return $str;
    } else {
        return substr($str, 0, $r_len - 1);
    }
}

function substr_fix($string, $len = 4) {
    $len *= 2;
    if (strlen($string) <= $len) {
        return $string;
    }
    $chinese = "(?:[".chr(228)."-".chr(233)."][".chr(128)."-".chr(191)."][".chr(128)."-".chr(191)."])";
    preg_match_all("/$chinese|\S|\s/", $string, $out);
    $string = '';
    foreach ($out[0] as $key => $val) {
        $len = strlen($val) == 1 ? $len - 1 : $len - 2;
        if ($len < 0) {
            break;
        }
        $string .= $val;
    }

    return trim_right($string);
}


// 获取客户端ip
function get_client_ip()
{
    if (isset($_SERVER['HTTP_CLIENT_IP']))
    {
         $onlineip = $_SERVER['HTTP_CLIENT_IP'];
    }
    elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
    {
         $onlineip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    elseif (isset($_SERVER['REMOTE_ADDR']))
    {
         $onlineip = $_SERVER['REMOTE_ADDR'];
    }
    else
    {
        return 'unknown';
    }

    return filter_var($onlineip, FILTER_VALIDATE_IP) !== false ? $onlineip : 'unknown';
}

/**
 * 获取用户所在城市
 * Enter description here ...
 * @param unknown_type $ip
 */
function get_ip_address($ip){
	
	if ($ip == '127.0.0.1') return '中国';
	include_once ROOT_PATH . '/lib/ip_area.php';
	$ips = new ip_area();
	$local = $ips->getCityAddress($ip);
	return $local;
}

/**
 * 
 * 错误提示函数
 * @param unknown_type $msg  错误提示消息
 * @param unknown_type $jumpurl  跳转url
 * @param unknown_type $notice success|error|notice
 * @param unknown_type $t   自动跳转时间
 */
function msg($msg, $jumpurl='', $notice='notice', $t = 3) {
	if (empty($_SESSION)){
		Response::redirect('/login');
		return ;
	}
    $smarty = $GLOBALS['view'];
    if ($jumpurl)
    {
        $jumpurl = htmlspecialchars($jumpurl);
        if (substr($jumpurl, 0, 4) != 'http') {
            if ($jumpurl{0} != '/') {
                $jumpurl = '/'.$jumpurl;
            }
            $jumpurl = SITE_URL.$jumpurl;
        }
        $ifjump = "<META HTTP-EQUIV='Refresh' CONTENT='$t; URL=$jumpurl'>";
        $smarty->assign('jumpurl', $jumpurl);
        $smarty->assign('ifjump', $ifjump);
    }

	$smarty->assign('type', $notice);
    $smarty->assign('msg', $msg);
    $smarty->display('msg.html');
    exit;
}

function up_session($info)
{
    $_SESSION['user_id'] = $info['id'];
    foreach ($info as $k => $v) {
        if (is_numeric($k)) {
            unset($info[$k]);
        }
    }
    if (!empty($info['id'])) {
        unset($info['id']);
    }
    if (!empty($info['password'])) {
        unset($info['password']);
    }

    $_SESSION = array_merge($_SESSION, $info);
}


function check_auth($controller, $action)
{
	if ($controller == 'login' or $controller == 'api') return true;
	
	if (!empty($_SESSION['privileges'])) {
		$privilege = implode(",",$_SESSION['privileges']);
	} else {
		return false;
	}
	if ($privilege == "*")  return true;

	$menu = _model("admin_menu")->read(array("controller"=>$controller, "action"=>$action));

	if ($menu) {
		if (in_array($menu['id'], $_SESSION['privileges'])) {
			return true;
		} else {
			return false;
		}
	} else {
		$menu = _model("admin_menu")->read(array("controller"=>$controller, "action"=>""));
		if (empty($menu)) {
		    return false;
		}
		if (in_array($menu['id'], $_SESSION['privileges'])) {
		    return true;
		} else {
			return false;
		}
	}
}


function _encode($data){
	return json_encode($data);
}




// 格式化日期
function format_date($limit)
{
	$h = $limit/3600;
	return  sprintf("%.2f", $h) . '小时';
}

/**
 * 格式化 config 中的配置信息
 * @param unknown_type $id
 * @param unknown_type $type
 */
function config_name($id, $type)
{
	include ROOT_PATH . '/config/ante_config.php';
	if((empty($id) or empty($type))) return ;
	
	$_config = Config::get($type);
	
	return empty($_config[$id]) ? "未知[$id]" : $_config[$id];
	
}

function get_user_info($unique_id){
	//echo "unique_id:$unique_id";
    $user_info = _model('user_data','wahz')->getField('*',array('unique_id'=>$unique_id));
	$str = '';
	if($user_info and !empty($user_info)){
			$str .= $user_info['phone_no'].'--';
		    $str .= $user_info['user_name'].'--';
		    $str .= $user_info['address'].'---';
		    $str .= $user_info['zcode'];
	}
	return $str?$str:'未知';
}

function get_reward_info($reward_type){
	//echo "type:$reward_type";
	$reward_info = _model('config_reward')->getField('name',array('id' => $reward_type));
	$str = '';
	if($reward_info and !empty($reward_info)){
		$str = $reward_info['name'];
	}
	return $str?$str:"未知奖励-$reward_type";
}

function http_post($url,$data ){     
    //var_dump($url);
    //var_dump($data);
	$curl = curl_init($url); // 启动一个CURL会话    
	curl_setopt($curl, CURLOPT_HTTPHEADER, 
		array(
			'Content-Type: application/json' , 
			'Content-Length: ' . strlen($data)
		));  
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查    
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1); // 从证书中检查SSL加密算法是否存在    
	curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);     
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转    
	curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer    
	curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包      
	curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环    
	curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容    
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回    
	$tmpInfo = curl_exec($curl);    
	if (curl_errno($curl)) {    
		return 'Errno'.curl_error($curl);    
	}    
	curl_close($curl);     
	return $tmpInfo;  
} 

function decode_extra_info($extra_info)
{
    $length = strlen($extra_info);
    $bitlen = $length * 8;
    if($length < 2)
        return "null";
    //$array = unpack("SinfoLen/",$extra_info);
    //if($array["infoLen"] != $length)
     //   return "decord error len:$length,raw data:".sprintf("%08b",$extra_info).'</br>';
    $format = '';
    for($i=0;$i<8;++$i)
    {
        $format .= "CbossBit_$i";
    }
    $format .= "ItankMaxPower/StankKinds/CtankMaxLevel/astringInfo/";
    $array = unpack($format,$extra_info);
    $decodeStr = '';
    $decodeStr .= '打了哪些Boss:0x';
    for($i=0;$i<8;++$i)
    {
        $decodeStr .= printf("%08b",$array["bossBit_$i"]);
    }
    $decodeStr .= '</br>';
    $decodeStr .= '战车最大战力:'.$array['tankMaxPower'].'</br>';
    $decodeStr .= '战车种类:'.$array['tankKinds'].'</br>';
    $decodeStr .= '战车最大等级:'.$array['tankMaxLevel'].'</br>';
    $decodeStr .= '其他信息：'.$array['stringInfo'].'</br>';
    return $decodeStr;
}

// Weapon store
function decode_tank_store($store)
{
    $length = strlen($store);
    if($length < 4)
        return "null";
    if($length % 4 != 0)
        return "decode error";
    $weaponNum =  $length / 4;
    $format = "";
    for($i=0;$i<$weaponNum;++$i)
    {
        $format .= "IweaponID_$i/";
    }
    $array = unpack($format,$store);
    $decodeStr = '';
    for($i=0;$i<$weaponNum;++$i)
    {
        $decodeStr .= $array["weaponID_$i"]."-";
    }
    $decodeStr = rtrim($decodeStr,'-');
    return $decodeStr;
}

function encode_tank_store($store)
{
    $length = strlen($store);
    if($length < 4)
	return "null";
    $store = rtrim($store,'-');
    //var_dump($store);
    $storeArray = explode('-',$store);
    //var_dump($storeArray);
    if(empty($storeArray))
	return "null";
    $storeBinary = null;
    foreach ($storeArray as $value)
    {
	//echo "value:$value";
	$storeBinary .= pack("I",$value);
    }
    //var_dump("encode ok!");
    return $storeBinary;
}

/*
 * 战车性能二进制格式描述：
 * 性能格式：
 * (4字节性能ID+1字节性能状态) * n
 * 例如：
 * （1001 + 1） + ( 1002 + 1 )
 * 性能ID和性能状态
 * 性能状态 1 : 已解锁  2 : 已开发 3 : 已禁用
 */

function decode_tank_property($property)
{
    $length = strlen($property);
    if($length < 2)
        return "null";
    if($length % 5 != 0)
        return "decode error";
    $propNum =  $length / 5;
    $format = "";
    for($i=0;$i<$propNum;++$i)
    {
        $format .= "IpropID_$i/CpropST_$i/";
    }
    $array = unpack($format,$property);
    $decodeStr = '';
    for($i=0;$i<$propNum;++$i)
    {
        $decodeStr .= $array["propID_$i"]."-";
        if($array["propST_$i"] === 1)
        {
            $decodeStr .= "已解锁\t";
        }
        elseif($array["propST_$i"] === 2)
        {
            $decodeStr .= "已开发\t";
        }
        elseif($array["propST_$i"] === 3)
        {
            $decodeStr .= "已禁用\t";
        }
    }
    return $decodeStr;
}

/**
 * 图标格式化 最小刻度 or 显示格式
 * Enter description here ...
 * @param unknown_type $value
 * @param unknown_type $type  datetime | tooltip | cate
 */
function chartsformat($value, $type='datetime'){
	if ($type == 'datetime') {
		switch($value){
			case $value < 10:
				$_tick = 3600*24*1*1000;
				break;
			case $value < 20:
				$_tick = 3600*24*2*1000;  
				break;
			case $value < 50:
				$_tick = 3600*24*3*1000;  
				break;
			case $value > 50:
				$_tick = 3600*24*7*1000;  // 最小刻度为1周
			    break;	
		}
		return $_tick;
	} 
}

/**
* 生成随机数
* @para int $length 要生成的长度
* @para int $numeric 为空则返回字母与数字混合的随机数,不为空则纯数字
*
* @return string 返回生成的字串
*/
function random($length, $numeric = 0) {
    PHP_VERSION < '4.2.0' ? mt_srand((double)microtime() * 1000000) : mt_srand();
    $seed = base_convert(md5(print_r($_SERVER, 1).microtime()), 16, $numeric ? 10 : 35);
    $seed = $numeric ? (str_replace('0', '', $seed).'012340567890') : ($seed.'zZ'.strtoupper($seed));
    $hash = '';
    $max = strlen($seed) - 1;
    for($i = 0; $i < $length; $i++) {
        $hash .= $seed[mt_rand(0, $max)];
    }
    return $hash;
}

function get_db_config($params){
	$model = 'config_'.$params;
	$rs = _model($model)->getList();
	
	$arr = array();
	foreach ( $rs as $k=>$v ) {
		if ($v['id'] and $v['name'])
		$arr[$v['id']] = $v['name'];
	} 
	return $arr;
}



?>
