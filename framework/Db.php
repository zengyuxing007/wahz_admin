<?php

class Db
{
    public $sqls = array();
    public $pdo = null;
    public $pdo_master = null;
    public $pdo_slave = null;
    private $config = array();
	private $mem = '';

    function __construct()
    {
    	$faram = func_get_args();
    	$this->config = $faram;

		//$this->mem = new SharedMemory();
    }
    
    private function getlink($type = 'slave'){
    	if ($type == 'slave'){
    		if (is_object($this->pdo_slave))
    			return $this->pdo_slave;
    		
    		$this->pdo_slave = call_user_func_array(array(new ReflectionClass('PDO'), 'newInstance'), $this->getParam());
    		$this->pdo_slave->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
    		
    	} elseif ($type == 'master'){
    		if (is_object($this->pdo_master))
    			return $this->pdo_master;
    		
    		$this->pdo_master = call_user_func_array(array(new ReflectionClass('PDO'), 'newInstance'), $this->getParam());
    		$this->pdo_master->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
    	} 
    }
    public function getParam(){

    	if (isset($this->config[0]) and $this->config[0]){
    		$this->database = $this->config[0];
    	} else $this->database = 'wahz';

    	$args = Config::get($this->database);

    	return array(
    		"mysql:host={$args['host']};dbname={$this->database}", 
    		$args['user'], 
    		$args['pwd'],
    		array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$args['charset']}"),
    	);
    	
    }

    public static function set_config($config)
    {
        $this->config = $config;
    }

    public function begin()
    {
        return $this->pdo->beginTransaction();
    }

    public function rollBack()
    {
        return $this->pdo->rollBack();
    }

    public function commit()
    {
        return $this->pdo->commit();
    }

    public function exec()
    {
        $args = func_get_args();
        $sql = array_shift($args);

        if ($args)
        {
            return $this->execute($sql, $args)->rowCount();
        }
        return $this->pdo->exec($sql);
    }

    public function execute($sql, $param)
    {	
    	if (strtoupper($sql[0]) == 'I' or strtoupper($sql[0] == 'U')){
			$this->getlink('master');
			$this->pdo = $this->pdo_master;
    	} else {
    		$this->getlink('slave');
    		$this->pdo = $this->pdo_slave;
    	}
    	
    	if (defined('D_BUG') && D_BUG){
	    	$mtime = explode(' ', microtime());
			$sqlstarttime = $mtime[1] + $mtime[0];
    	}
		
    	if (isset($param[0]) and is_array($param[0])) $param = $param[0];
        if ($param)
        {
            $sth = $this->pdo->prepare($sql);
            if (!$sth->execute($param))
            {
                throw new Exception("Error sql prepare:$sql");
            }
        }
        else
        {
            if (!$sth = $this->pdo->query($sql))
            {
                throw new Exception("Error sql query:$sql");
            }
        }
        
        if (defined('D_BUG') && D_BUG){
	    $mtime = explode(' ', microtime());
	    $sqltime = number_format(($mtime[1] + $mtime[0] - $sqlstarttime), 6)*1000;
	    $GLOBALS['sql'][] = array('database'=>$this->database, 'sql'=>$sql, 'time'=>$sqltime);
	    //error_log($sql."\n",3,ROOT_PATH . "/data/log/sqllog.txt");
	}

        return $sth;
    }

	function build_key($sql,$option, $table='') {
		if(is_array($option)) {
			$keys = '';
			foreach($option as $k=>$v){
				if (strlen($k) > 40) {
					$k = substr($k, 0, 40).md5($k).strlen($k);
				}
				$value=$v;
				if(is_array($v)){
					$value=current($v);
				}
				$keys .= $k . ':' . $value;			
			}
			$key = 'MEM'.$table .':' .$keys;
		}
		return sha1($sql.$key);
	}

    public function query()
    {
        $args = func_get_args();
        $sql = array_shift($args);
        return $this->execute($sql, $args);
    }

    public function getOne()
    {
        $args = func_get_args();
        $sql = array_shift($args);

		$key = $this->build_key($sql,$args);
				
		//$info = $this->mem->get($key);
		//if($info) return $info;

        if (stripos($sql, 'limit') === false)
        {
            $sql .= ' LIMIT 1';
        }
        $query = $this->execute($sql, $args);
		$val = $query->fetchColumn();
		//$this->mem->set($key,$val);

        return $val;
    }

    public function getCol()
    {
        $args = func_get_args();
        $sql = array_shift($args);

		global $M_EM;
		$key = $this->build_key($sql,$args);
		
		if(isset($M_EM[$key]) and $M_EM[$key]){
			return $M_EM[$key];
		}

        $query = $this->execute($sql, $args);
        if ($out = $query->fetchAll(PDO::FETCH_COLUMN, 0))
        {
            return $M_EM[$key] = $out;
        }

        return array();
    }

    public function getAll()
    {
        $args = func_get_args();
        $sql = array_shift($args);
        
        $query = $this->execute($sql, $args);

        if ($out = $query->fetchAll(PDO::FETCH_ASSOC))
        {
            return $out;
        }
        return array();
    }

    public function getRow()
    {
        $args = func_get_args();
        $sql = array_shift($args);
		global $M_EM;
		$key = $this->build_key($sql,$args);
		//echo "key:$key";
		
///*
		if(isset($M_EM[$key]) and $M_EM[$key]){
			return $M_EM[$key];
		}
//*/
        $query = $this->execute($sql, $args);

        return $M_EM[$key] = $query->fetch(PDO::FETCH_ASSOC);
    }

    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }
}
?>
