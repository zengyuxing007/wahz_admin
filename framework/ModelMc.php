<?php
abstract class ModelMc extends Model
{
	protected $db = null;
    public $error = '';
    public $mc = '';
    public $cache = 1;  // 1打开 0 关闭
    protected $lifetime = 600;
	protected $table_ns = '';  //表空间

    public function __construct($db)
    {
        if (!empty($this->cache)) {
            $this->mc = MemcacheWrapper::getInstance();

			$this->table_ns = $this->table .'ns';
        }
	
        $this->db = $db;
        if (method_exists($this, 'init'))
        {
            $this->init();
        }
    }
    
    public function read($option, $table = null)
    {
		$table === null and $table = $this->table;
		if(empty($this->cache)) return parent::read($option, $table);

		global $M_EM;
		$key = $this->build_key($option, $table);
		if(isset($M_EM[$key]) and $M_EM[$key]) 	{
			return $M_EM[$key];
		}
		
		// 4、如果memcahce存在，先放在内存，再返回
        $tem_arr = $this->mc->get($key);
        if ($tem_arr) {
        	$M_EM[$key] = unserialize($tem_arr);
        	return $M_EM[$key];
        }
        
        // 5、以上都不存在，执行sql查询
        $M_EM[$key] = parent::read($option);
        $this->mc->set($key, serialize($M_EM[$key]), MEMCACHE_COMPRESSED, 600);
        return $M_EM[$key];
    }
	//生成 key
	function build_key($option, $table) {
		if(is_array($option)) {
			$keys = '';
			foreach($option as $k=>$v){
				if (strlen($k) > 40) {
					$k = substr($k, 0, 40).md5($k).strlen($k);
				}
				$keys .= $k . ':' . $v;			
			}
			$key = 'MEM'.$table .':' .$keys;
		}
		return sha1($key);
	}


    public function delete($option, $table = null)
    {
        $table === null && $table = $this->table;
        // 缓存失效,使用命名空间
//    	if (is_object($this->mc_wr)) {
//    	    $this->mc_wr->NameSpace($table.'list_ns');
//    	    $this->mc_wr->delete();
//    	}

        $where = array();
        foreach ($option as $key => $val) {
            $where[] = "$key=?";
        }
        $sql = "DELETE FROM `$table` WHERE ".implode(' AND ', $where);
        return $this->db->execute($sql, array_values($option));
    }

    public function update($option, $array, $table = null)
    {
        $table === null && $table = $this->table;
    	// 缓存失效,使用命名空间
//    	if (is_object($this->mc_wr)) {
//    	    $this->mc_wr->NameSpace($table.'list_ns');
//    	    $this->mc_wr->delete();
//    	}

    	$set = array();
        foreach ($array as $key => $val) {
            $set[] = "$key = ?";
        }

        $param = array_values($array);

        $where = array();
        foreach ($option as $key => $val) {
            $where[] = "$key=?";
            $param[] = $val;
        }

        $sql = "UPDATE `$table` SET ".implode(',', $set)." WHERE ".implode(' AND ', $where);
        return $this->db->execute($sql, $param);
    }

    public function create($param, $table = null)
    {
        $table === null && $table = $this->table;

    	// 缓存失效,使用命名空间
//    	if (is_object($this->mc_wr)) {
//    	    $this->mc_wr->NameSpace($table.'list_ns');
//    	    $this->mc_wr->delete();
//    	}

        $sql = "INSERT INTO `$table` (".implode(', ', array_keys($param)).') VALUES ('.implode(', ', array_fill(0, count($param), '?')) . ')';
        $result = $this->db->execute($sql, array_values($param));
        $id = $this->db->lastInsertId();

        return $id;
    }


    public function getList($option=array(), $limit = '', $table = null)
    {
       $table === null && $table = $this->table;

    	if (empty($option)) {
    	    $option = (array) $option;
    		$sql = "SELECT * FROM `$table` $limit";
    	}
    	else {
        	$where = array();
            foreach ($option as $key => $val) {
                $where[] = "$key=?";
            }
            $sql = "SELECT * FROM `$table` WHERE ".implode(' AND ', $where)." $limit";
    	}
    	//echo $sql;
        return $this->db->getAll($sql, array_values($option));
    }

    public function getTotal($option=array(), $table = null)
	{
	    $table === null && $table = $this->table;

	    if (empty($option)) {
	    	$sql = "SELECT COUNT(*) FROM `$table`";
	    }
	    else {
    	    $where = array();
            foreach ($option as $k => $v) {
                $where[] = "$k=?";
            }
            $sql = "SELECT COUNT(*) FROM `$table` WHERE ".implode(' AND ', $where);
	    }
	    return $this->db->getOne($sql, array_values($option));
	}


	public function offsetGet($key)
    {
        return $this->read(array('id'=>$key));
    }


    public function offsetExists($key)
    {
        if ($this->read(array('id'=>$key))) {
            return true;
        }

        return false;
    }


    public function offsetSet($key, $val)
    {
        return $this->update(array('id'=>$key), $val);
    }


    public function offsetUnset($key)
    {
        return $this->delete(array('id'=>$key));
    }
    
}
?>