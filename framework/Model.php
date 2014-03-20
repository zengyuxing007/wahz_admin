<?php
abstract class Model implements ArrayAccess
{
    protected $db = null;
    public $error = '';

    public function __construct($db)
    {
        $this->db = $db;
        if (method_exists($this, 'init'))
        {
            $this->init();
        }
    }
    
    /**
     * 专为 sql 生成器订制 方法
     * Enter description here ...
     * @param unknown_type $sql
     */
    public function runsql($sql,$param=''){
    	return $this->db->getAll($sql,$param);
    }
    
    /**
     * 读取1条记录
     * @param array $option WHERE条件数组
     * @param string $table 表名
     * @return array
     */
    public function read($option, $table = null)
    {
        $table === null && $table = $this->table;

        if (is_numeric($option)){$id = $option;}
    	elseif (isset($option['id'])) {$id = 0+$option['id'];}

        $where = array();
        foreach ($option as $key => $val) {
            $where[] = "$key=?";
        }
        $sql = "SELECT * FROM `$table` WHERE ".implode(' AND ', $where);

        return $this->db->getRow($sql, array_values($option));
    }
    
    /**
     * 读取一个值
     * Enter description here ...
     */
    function getFields( $field, $option, $table=null)
    {
    	$table === null && $table = $this->table;
    		    	
    	if (empty($field)) $field = 'id';
    	
    	$where = $this->arrayToWhere(&$option);
    	
        $sql = "SELECT {$field} FROM `{$table}` {$where} ";
        
    	return $this->db->getCol($sql, array_values($option));
    }
    
    function getField( $field,$option,$table=null)
    {
     	$table === null && $table = $this->table;
    		    	
    	if (empty($field)) $field = 'id';
    	
    	$where = $this->arrayToWhere(&$option);
    	
        $sql = "SELECT {$field} FROM `{$table}` {$where} ";

//		echo "sql:$sql";

		//print_r($option);

//echo "values:";
//		print_r(array_values($option));
//echo "---";
	
        
    	return $this->db->getRow($sql, array_values($option));   	
    	
    }

    /**
     * 删除表记录
     * @param array $option WHERE条件数组
     * @param string $table 表名
     * @return bool 执行的结果
     */
    public function delete($option, $table = null)
    {
        $table === null && $table = $this->table;

        $where = array();
        foreach ($option as $key => $val) {
            $where[] = "$key=?";
        }
        $sql = "DELETE FROM `$table` WHERE ".implode(' AND ', $where);
        return $this->db->execute($sql, array_values($option));
    }

    /**
     * 更新表记录
     * @param array $option WHERE条件数组，只支持于全等
     * @param array $array 更新的内容
     * @param int 更新的记录数
     */
    public function update($option, $array, $table = null)
    {
        $table === null && $table = $this->table;

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

    /**
     * 写入1条记录
     * @param array $array
     * @param string $table
     */
    public function create($param, $table = null)
    {
        $table === null && $table = $this->table;

        $sql = "INSERT INTO `$table` (".implode(', ', array_keys($param)).') VALUES ('.implode(', ', array_fill(0, count($param), '?')) . ')';
        $result = $this->db->execute($sql, array_values($param));
        $id = $this->db->lastInsertId();

        return $id;
    }

    public function executesql($param,$table = null)
    {
	try
	{
	    $table === null && $table = $this->table;
	    $sql = "INSERT INTO `$table` (".implode(', ', array_keys($param)).') VALUES ('.implode(', ', array_fill(0, count($param), '?')) . ')';
	    $result = $this->db->execute($sql, array_values($param));
	}
	catch(Exception $e)
	{
	    echo "Error:$e";
	    return false;
	}
	return true;
    }

    /**
     * 读取指定资源列表
     * @param array $option 条件数组，可为空
     * @param string $limit SQL中的limit语句，可添加order by
     * @param string $table 指定表
     */
    public function getList( $option=array(), $limit = '', $table = null)
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
        
    /**
     * @todo实验方法
     * @example getAll("1,2,...","LIMIT 10") //获取字段为id  的 10调记录
     * @example getAll(array('name'=>'gemor'))//获取name 为 gemor 的记录
     * @example getAll(array('id'=>array(2,3,4,10,20)), "LIMIT 10") //获取 id in(2,3,..) //驱动不支持 暂且不能使用
     * @example getAll(array('name'=>'%gemor%')) // LIKE %gemor% 
     * @example getAll(array('time<'=>'20111111'))
     */
    public function getAll( $field, $option, $limit='', $table=null)
    {
    	$table === null && $table = $this->table;
    	
    	if (empty($field)) $field = 'id';
    	
    	$where = $this->arrayToWhere(&$option);
    	
        $sql = "SELECT {$field} FROM `{$table}` {$where} {$limit}";
        
        return $this->db->getAll($sql, is_string($option) ?'':array_values($option));
    	
    }
    
	/**
     * 构造WHERE条件SQL语句
     * @param $array  1维条件数组，array('id'=> 2010)
     * @todo $array array('ids'=>array(1,2)) | WHERE `ids` IN (1,2) 驱动暂且不支持
     * @return string 1、WHERE `res_id`=? AND `res_type`=?，2、WHERE `res_id` IN(?,?,?)，3、WHERE `name` LIKE ?
     * @todo 
     */
    public function arrayToWhere($option)
    {
        if (empty($option)) {
            // 0、条件为空取所有
            return "";
        } elseif (is_string($option)) {
            if (is_numeric($option[0])) {
                // 2、'1,2,3,4,5'
                $option = explode(',', $option);
                return "WHERE `id` IN(" . implode(',', array_fill(0, count($option), '?')) . ")";
            } else {
                // 1、WHERE，LIMIT
                return $option;
            }
        } elseif (is_numeric($option)) {
            // 3、如果只是1个id
            return "WHERE `id`='{$option}'";
        }


        $k = key($option);
        $v = current($option);
        if (is_numeric($k)) {
            // 1、如果是1维数组，array(1,2,3,4);
            return "WHERE `id` IN(" . implode(',', array_fill(0, count($option), '?')) . ")";
        } elseif (is_array($v)) {
            // 2、如果是2维数组，array('id'=>array(1,2,3,4))
            return "WHERE `{$k}` IN(" . implode(',', array_fill(0, count($v), '?')) . ")";
        }

        $where = array();
        foreach ($option as $k => $v) {
        	//先匹配 value
        	if (preg_match("/%/", $v)) $where[] = "`{$k}`" . $this->cv(&$v);
            //在匹配 key
            elseif (preg_match("/(\s|<|>|!|=|is null|is not null)/i", $k, $match))
            	$where[] = "{$k} ?";
            else $where[] = "`{$k}`=?"; 
        }
        $sql = "WHERE ".implode(' AND ', $where);
        return $sql;
    }
    
    /**
     * 检查是否是搜索
     * %sss,sss%,%sss%
     * @param string $v 条件值
     * @return string 如果是搜索返回  LIKE ?，否则返回 =?
     */
    public function cv($v)
    {
        $v = trim($v);
        if (is_string($v) && (substr($v, 0, 1) == '%' || substr($v, -1, 1) == '%')) {
            return " LIKE ?";
        } else {
            return "=?";
        }
    }
    

    /**
     * 统计相关条件记录数
     * @param array $option 条件数组，如果为空统计全表记录数
     * @return int
     */
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

    /**
     * 读取指定id内容
     * @param intval $key
     * @return mixed array\bool
     */
	public function offsetGet($key)
    {
        return $this->read(array('id'=>$key));
    }

    /**
     * 判断指定id是否存在
     * @param intval $key
     * @return bool
     */
    public function offsetExists($key)
    {
        if ($this->read(array('id'=>$key))) {
            return true;
        }

        return false;
    }

    /**
     * 更新指定id记录
     * @param intval $key
     * @param array $val
     * @return bool
     */
    public function offsetSet($key, $val)
    {
        return $this->update(array('id'=>$key), $val);
    }

    /**
     * 删除指定id记录
     * @param intval $key
     */
    public function offsetUnset($key)
    {
        return $this->delete(array('id'=>$key));
    }
    
}
?>
