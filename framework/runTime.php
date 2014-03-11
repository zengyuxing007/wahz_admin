<?php 
class runtime
{ 
    static  $StartTime = 0; 
    static  $StopTime = 0; 
 
    static function get_microtime() 
    { 
        list($usec, $sec) = explode(' ', microtime()); 
        return ((float)$usec + (float)$sec); 
    } 
 
    static function start() 
    { 
        self::$StartTime = self::get_microtime(); 
    } 
 
    static function stop() 
    { 
        self::$StopTime = self::get_microtime(); 
    } 
 
    static function spent() 
    { 
        return round((self::$StopTime - self::$StartTime) * 1000, 1); 
    } 
 
}
?>