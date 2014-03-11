<?php
require_once('simple-dal.php');

class MySQLDAL implements SimpleDAL {
	#if you want to plug in your own DAL, do so here
	#these functions just wrap the mysql_ functions.  This works great if you are using mysqlnd.

	private $conn=null;
	private $stmt=null;

	function my_connect($server=null) {
		if(!$server) return false;

		if(empty($server['port'])) {
			$port = 3306;
		} else {
			$port = $server['port'];
		}

		if(empty($server['user'])) $server['user'] = 'root';
		if(empty($server['password'])) $server['password'] = '';

		if(!empty($server['options'])) {
			$conn = mysql_connect($server['host'] . ':' . $port, $server['user'], $server['password'],true,$server['options']);
		} else {
			$conn = mysql_connect($server['host'] . ':' . $port, $server['user'], $server['password'],true);

		}

		if(!empty($server['db'])) mysql_select_db($server['db']);
		$this->conn = $conn;
		return $conn;
	}

	function my_query($sql='', $conn=null) {
		if($conn) {	
			$this->stmt = mysql_query($sql, $conn);
		} else {
			$this->stmt = mysql_query($sql, $this->conn);
		}
		return $this->stmt;
	}

	function my_fetch_assoc($stmt=null) {
		if(!$stmt) $stmt=$this->stmt;
		return mysql_fetch_assoc($stmt);
	}

	function my_fetch_array($stmt=null) {
		if(!$stmt) $stmt=$this->stmt;
		return mysql_fetch_array($stmt);
	}

	function my_errno($stmt = null) {
		if(!$stmt) return mysql_errno($this->conn);
		return mysql_errno($stmt);
	}

	function my_error($stmt = null) {
		if(!$stmt) return mysql_error($this->conn);
		return mysql_error($stmt);
	}

	function my_select_db($db, $conn=null) {
		if($conn) return mysql_select_db($db, $conn);
		return mysql_select_db($db,$this->conn);
	}

	function my_free_result($stmt = null) {
		if($stmt) return mysql_free_result($stmt);
		if($this->stmt) return mysql_free_result($this->stmt);
		return true;
	}

	function my_close($conn = null) {
		if($conn) return mysql_close($conn);
		if($this->conn) return mysql_close($this->conn);
		return false;
	}

	function my_begin($conn = null) {
		if(!$conn) {
			return mysql_query('begin',$this->conn);
		} else {
			return mysql_query('begin',$conn);
		}
	}

	function my_autocommit($state = true, $conn = null) {
		if($state) $state = 'true'; else $state = 'false';
		if(!$conn) {
			return mysql_query("set autocommit=$state",$this->conn);
		} else {
			return mysql_query("set autocommit=$state",$conn);
		}
	}

	function my_commit($conn = null) {
		if($conn) {
			return mysql_query('commit', $conn);
		} else {
			return mysql_query('commit',$this->conn);
		}
	}

	function my_rollback($conn = null) {
		if($conn) {
			return mysql_query('rollback', $conn);
		} else {
			return mysql_query('rollback',$this->conn);
		}
	}

	function my_real_escape_string($string, $conn = null) {
		if($conn) {
			return mysql_real_escape_string($string, $conn);
		}
		return mysql_real_escape_string($string,$this->conn);
	
	}


	#END DAL
}
