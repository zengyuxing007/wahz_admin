<?php
global $api_uri;
$api_uri = 'api.app.7k7k.com';

include 'mapping.php';

/**
 *
 * include '/path/to/apiclient.php';
 * $k7api = new ApiClient('passport');
 * $k7api->getLoginInfo();
 *
 * or
 *
 * include '/path/to/apiclient.php';
 * $k7api = new ApiClient('my');
 * $k7api->getUserBasic(1);
 *
 */
class ApiClient {
  private $api;
  private $map;

  public function __construct($api = 'passport') {
    global $api_uri;
    $map = "api_mapping_$api";
    global $$map;
    $this->map = $$map;
    $this->api = "http://$api_uri/$api";
  }

  public function __call($name, $arguments) {
    if(!isset($this->map[$name])) return null;
    $content = http_build_query($arguments);
    $jsonResponse = @file_get_contents($this->api."/".$this->map[$name], false, stream_context_create (
            array ('http' =>
                array (
                      'method' => 'POST',
                      'header' => "Accept-Language: zh-cn\r\n".
                                  "Content-type: application/x-www-form-urlencoded\r\n".
                                  "Connection: close\r\n".
                                  "Content-Length: ".strlen($content)."\r\n".
                                  "Cookie: ".self::_constructCookie()."\r\n",
                      'content' => $content,
                	  'timeout' => 1 //set timeout 1s
            ))));
    if ($jsonResponse === false)
      return null;
    $jsonResponse = json_decode($jsonResponse, true);
    $content = is_array($jsonResponse);
    if ($jsonResponse === null || ($content && isset($jsonResponse['error'])))
      return null;
    else {
      global $api_setcookie;
      if (isset($api_setcookie[$name])) {
        $t = isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] + 24 * 3600 * 365 : time() + 24 * 3600 * 365;
        foreach($http_response_header as $s){
          if(preg_match('|^Set-Cookie:\s*([^=]+)=([^;]+);(.+)$|', $s, $parts)) {
            $p3 = explode(';', $parts[3]);foreach($p3 as $p) {if (strpos($p,'expires')) {$v = explode('=', $p); $t = strtotime($v[1]); break;}}
            setcookie($parts[1], $parts[2], $t, '/', '7k7k.com');
          }
        }
      }
      return $jsonResponse;
    }
  }

  private static function _constructCookie(){
    if (!is_array($_COOKIE)) return '';
    $cookie = '';
    foreach ($_COOKIE AS $key => $value) {
      $cookie .= "$key=$value; ";
    }
    return $cookie;
  }
}
