<?php

class Action
{
    protected $res_type = 11;

    public function __call($param = '', $param=array())
    {
    }

	protected function _push($token_array,$message,$is_production,& $desc)
    {

			if(!is_array($token_array) || !$message) {return false;}

			//var_dump($token_array);
			//exit;

			// Put your private key's passphrase here:
			$passphrase = 'jesse';

			// Put your alert message here:
//			$message = 'My first push test!';

			$ctx = stream_context_create();
			if($is_production){
					stream_context_set_option($ctx, 'ssl', 'local_cert', ROOT_PATH.'/ca/ck_production.pem');
			}else{
					stream_context_set_option($ctx, 'ssl', 'local_cert', ROOT_PATH.'/ca/ck.pem');
			}
			stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

			// Open a connection to the APNS server
			$fp;
			if($is_production){

					$fp = stream_socket_client(
									'ssl://gateway.push.apple.com:2195', $err,
									$errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);
			}else{
					$fp = stream_socket_client(
									'ssl://gateway.sandbox.push.apple.com:2195', $err,
									$errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);
			}

			if (!$fp){
					$desc = "Failed to connect: $err $errstr" . PHP_EOL;
					return false;
			}

			//echo 'Connected to APNS' . PHP_EOL;

			// Create the payload body
			$body['aps'] = array(
							'alert' => $message,
							'sound' => 'default'
							);

			// Encode the payload as JSON
			$payload = json_encode($body);

			foreach ($token_array as $key => $val){
			
					//echo $val['device_token'];
					//exit;
					// Build the binary notification
					$msg = chr(0) . pack('n', 32) . pack('H*', $val['device_token']) . pack('n', strlen($payload)) . $payload;

					// Send it to the server
					$result = fwrite($fp, $msg, strlen($msg));
			}
			fclose($fp);
			return true;

	}


	public function index()
	{
			$tpl = array(
							'title' => '消息推送',
							'desc'  => '',
							'helper' => false //是否显示帮助信息
						);
			Response::assign('tpl', $tpl);

			$message =  Request::get('message', '0');
			$is_production =  Request::get('is_production', 0);
			if(!$message){
					Response::display('push.html');
					return;
			}

			$field = "device_token";

			$data = _model('device_info','wahz')->getAll($field,array());

		    $desc='';
			if(!$this->_push($data,$message,$is_production,$desc)){
				
			}
			else{
				$desc = '推送成功!!!';
			}
			msg($desc);
	}
}
