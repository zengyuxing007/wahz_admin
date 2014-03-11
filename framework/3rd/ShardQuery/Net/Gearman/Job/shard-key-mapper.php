<?php
error_reporting(E_ALL);

interface ShardKeyMapper {

	public function __construct($config = null); 
	
	#return a list of shards
	public function get_shards();
	
	#create the shard data store online (this is used for initial setup)
	public function create_store();
	
	public function drop_store();
	
	#online add a shard to the system
	public function add_shard($shard_name, $shard_type);

	#online remove a shard from the system
	public function remove_shard($shard_id);

	public function add_column($column);

	#online move keys between shards
	#public function move_key($column, $key,$tables, $dest_shard_id);
	public function move_key($column, $key, $tables, $dest_shard );

	#lock a partition key (make it so that no rows on the shard may be modified)
	public function lock_key($column, $key, $mode);

	#unlock a key
	public function unlock_key($lock);

	#create a new key 
	public function new_key($column, $shard);

	#get all the keys which belong to a particular shard
	public function get_keys($column, $shard);

	#get a distribution of how many of each column are on each shard
	#return an array of shard_id => estimated_count
	public function get_key_distribution($column);

	#return an index into the shards returned by get_shards for the given key for the given column
	public function map($column, $key); 

	public function get_columns(); 
}


class HashShardKeyMapper implements ShardKeyMapper{
	var $shards = array();

	public function __construct($config = array()) {
		$this->shards = $config;
	} 
	
	public function get_shards() {
		return $this->shards;
	}
	
	public function add_shard($shard, $shard_type) {
		throw new Exception('Can not add shard: Adding a shard would change the hash distribution');
	}

	public function remove_shard($shard_id) {
		throw new Exception('Can not remove shard: Removing a shard would change the hash distribution');
	}
	
	#create the shard data store online (this is used for initial setup)
	public function create_store() {
		throw new Exception('Hash partitioning does not use a data store');
	}
	
	public function drop_store() {
		throw new Exception('Hash partitioning does not use a data store');
	}

	public function add_column($column) {
		$this->columns[$column] = $column;
	}

	public function move_key($column, $key, $tables, $dest_shard ) {
		throw new Exception('Hash partitions are fixed.');
	}

	public function lock_key($column, $key, $lock_mode) {
		throw new Exception('Locks are not supported.');
	}
	public function unlock_key($lock) {
		throw new Exception('Locks are not supported.');
	}

	#get a new key value for the given partition
	public function new_key($column, $shard) {
		throw new Exception('Hash partitioning does not support key generation');
	}

	#get all the keys which belong to a particular shard
	public function get_keys($column, $shard) {
		throw new Exception('Hash partitioning does not use a data store');
	}

	public function get_key_distribution($column) {
		throw new Exception('Hash partitioning does not use a data store');
	}

	#returns a shard object
	public function map($column, $key) {
		static $cache = array();
		if(!empty($cache[$key])) return $cache[$key];

		$shard_ids = array_keys($this->shards);
		if(is_numeric($key)) {
			$shard_id = $shard_ids[ $key % count($shard_ids) ];
		} else {
			$shard_id = $shard_ids[ (abs(crc32($key)) % count($shard_ids)) ];
		}
		$cache[$key] = $shard_id;
		return $shard_id;
	}

	public function get_columns()  {
		return array_values($this->columns);
	} 

	
}

class DirectoryShardKeyMapper implements ShardKeyMapper{
	private $debug = false;
	var $shards = array();
	var $columns = array();
	var $config = null;
	var $conn = false;  # The connection handle for the directory server
	
	private function connect() {

		if($this->conn) return $this->conn;
		$host = $this->config['host'];
		if(!empty($this->config['port'])) $host .= ':' . $this->config['port'];

		$this->conn = mysql_connect($host, $this->config['user'], $this->config['password']);
		if(!$this->conn) {
			throw new Exception('Trying to connect to directory server: ' . mysql_error());
		}
		$this->changedb($this->config['db'],true); 

		return $this->conn;
	}

	private function changedb($db, $ignore = false ) {
		if(!mysql_select_db($db, $this->conn)) {
			if(!$ignore) throw new Exception('Could not switch to db: ' . $this->config['db']);
			return false;
		}
		return true;
	}

	private function execute($sql) {
		if ($this->debug) echo $sql . "\n";
		$stmt = mysql_query($sql,$this->conn) or die(mysql_error());
		if(!$stmt) throw new Exception('Error while running:' . "\n$sql\n" . mysql_error());
		return $stmt;
	}

	#The constructor accepts an array which includes host, user, password and db
	public function __construct($config = array('host'=>null,'port' => 3306, 'user'=>null,'password'=>null,'db'=>null)) {

		if(empty($config['host']) || empty($config['user']) || empty($config['db'])) {
			throw new Exception('Missing one of required options: host, user or db');
		}
		if(empty($config['password'])) $config['password'] = '';

		$this->config = $config;
	} 

	public function escape($val) {
		$this->connect();
		return mysql_real_escape_string($val, $this->conn);
	}
	
	#create the shard data store online (this is used for initial setup)
	public function create_store() {
		$this->connect();
		if(!$this->changedb($this->config['db'],true)) {
			$this->execute("CREATE DATABASE `" . $this->config['db'] . "`");
			$this->changedb($this->config['db'], false);
		}
# extra_info contains whatever info you want to send back to your application about this 
# shard.  This info might be a serialized PHP array for example.  You probably don't want
# to store sensitive information in this field unless you can protect it	
		$sql = "
CREATE TABLE IF NOT EXISTS shards (
  shard_name varchar(50) primary key,
  extra_info TEXT,
  shard_type ENUM('STORAGE','DIRECTORY') DEFAULT 'STORAGE' ,
  shard_id  mediumint auto_increment,
  key(shard_id)
) ENGINE=INNODB; ";
		$this->execute($sql);

		$sql = "
CREATE TABLE IF NOT EXISTS shard_columns (
  column_name varchar(50) primary key,
  column_id mediumint, 
  next_value bigint auto_increment,
  key(next_value)
) ENGINE=INNODB; ";
		$this->execute($sql);

		$sql = "
CREATE TABLE IF NOT EXISTS shard_sequence (
  column_name varchar(50) primary key,
  column_id mediumint, 
  next_value bigint auto_increment,
  key(next_value)
) ENGINE=INNODB; ";
		$this->execute($sql);


		$sql = "
CREATE TABLE IF NOT EXISTS shard_map (
  column_id smallint,
  key_value bigint,
  shard_id mediumint,
  primary key(key_value,column_id),
  key(shard_id)
) ENGINE=INNODB; ";
		$this->execute($sql);

		
	}
	
	public function get_shards() {
		$this->connect();
		$sql = "SELECT shard_name, extra_info FROM shards where shard_type = 'STORAGE'";
		$stmt = $this->execute($sql);

		while($row = mysql_fetch_assoc($stmt)) {
			$shards[$row['shard_name']] = unserialize($row['extra_info']);
		}
	
		$this->shards = $shards;

		return $this->shards;
	}
	
	public function add_shard($shard_name, $extra_info, $type='STORAGE') {
		$shard_name = $this->escape($shard_name);
		$extra_info = $this->escape($extra_info);
	
		$sql = "INSERT INTO shards (shard_name, extra_info, shard_type) VALUES ('{$shard_name}','{$extra_info}','{$type}')";
		$this->execute($sql);
		$sql = "COMMIT";
		$this->execute($sql);

		$this->shards[$shard_name] = $extra_info;
	}

	public function remove_shard($shard_id) {
		throw new Exception('Not implemented');
	}
	
	public function drop_store() {
		$this->connect();
		if($this->changedb($this->config['db'],true)) { 
			$sql = "DROP TABLE IF EXISTS shards, shard_columns, shard_map";
			$this->execute($sql);
		}
	}

	public function add_column($column) {
		$column = $this->escape($column);

		$sql = "SELECT count(*) + 1 INTO @cnt from shard_columns";
		$this->execute($sql);

		$sql = "REPLACE INTO shard_columns (column_name, column_id) values ('$column', @cnt)";
		$this->execute($sql);
	}

	public function move_key($column, $key, $tables, $dest_shard ) {
		$this->debug = false;
		#connect to the directory 
		$this->connect();
		$this->execute('BEGIN');

		#the connection details are stored in a serialized array in $this->shards[$shard_name]
		$server1 = unserialize($this->shards[$this->map($column,$key)]);
		$server2 = unserialize($this->shards[$dest_shard]);

		$source_conn = mysql_connect($server1['host'] . ':' . $server1['port'], $server1['user'], $server1['password']);
		if(!$source_conn) {
        		die("Could not connect: " . mysql_error() . "\n");
		}

		if(!mysql_select_db($server1['db'], $source_conn)) {
        		die("Could not select db: " . mysql_error() . "\n");

		}

		$dest_conn = mysql_connect($server2['host'] . ':' . $server2['port'], $server2['user'], $server2['password']);
		if(!$dest_conn) {
		        die("Could not connect: " . mysql_error() . "\n");
		}

		if(!mysql_select_db($server2['db'], $dest_conn)) {
        		die("Could not select db: " . mysql_error() . "\n");

		}

		$key = $this->escape($key);

		#Both servers must start a transaction
		if(!mysql_query('BEGIN',$source_conn) || !mysql_query('BEGIN',$dest_conn)) {
			throw new Exception('Could not start transaction on both shards.');
		}

		#move the data between servers
		$stmt = mysql_query('select @@max_allowed_packet as max', $dest_conn);
		if(!$stmt) throw new Exception('While trying to get max_allowed_packet: MySQL error (' . mysql_errno() . ') ' . mysql_error());
		$row = mysql_fetch_assoc($stmt);
		$max_packet = $row['max'];

		#Read the data from the source database and write it into the dest database
		foreach($tables as $table) {
			$sql = "SELECT * FROM $table where $column = $key FOR UPDATE";
			if($this->debug) echo "$sql\n";
		
			$stmt = mysql_query($sql, $source_conn);
			if(!$stmt) throw new Exception('While reading from source shard: MySQL error (' . mysql_errno() . ') ' . mysql_error());

			$replace_head = "";
			while($row = mysql_fetch_assoc($stmt)) {
				#make a REPLACE statement using the columns from the SELECT statement
				if(!$replace_head) {
					$replace_head = "REPLACE INTO $table (" . '`' . join('`,`', array_keys($row)) . '`) VALUES ';
				} 
				$replace = "";
				foreach($row as $val) {
					if($replace) $replace .= ',';
					$replace .= "'" .  $this->escape($val) . "'";
				}
				$replace = "$replace_head ($replace)";
				if($this->debug) echo $replace . "\n";

				if(! mysql_query($replace,$dest_conn) ) {		
					 throw new Exception('While writing to new shard: MySQL error (' . mysql_errno() . ') ' . mysql_error());
				}
			}	
		}

		# now update the central directory
		$this->execute("UPDATE shard_map set shard_id = (select shard_id from shards where  shard_name = '$dest_shard') where column_id = (select column_id from shard_columns where column_name='$column') and key_value = $key");

		# commit the data in the directory and in the new server.  
		if(!mysql_query('COMMIT',$dest_conn)) throw new Exception('Could not commit on dest shard!');
		$this->execute('COMMIT');

		# delete the data on the source server
		foreach($tables as $table) {
			$sql = "DELETE FROM $table where $column = $key";
			if($this->debug) echo "$sql\n";
		
			$stmt = mysql_query($sql, $source_conn);
			if(!$stmt) throw new Exception('While deleting from source shard: MySQL error (' . mysql_errno() . ') ' . mysql_error());
		}

		#commit on the source server
		if(!mysql_query('COMMIT',$source_conn)) throw new Exception('Could not commit on source shard!');
		if($this->debug) echo "COMMIT\n";

		return true;
		
	}

	public function lock_key($column, $key, $lock_mode) {
		throw new Exception('Not implemented.');
	}
	public function unlock_key($lock) {
		throw new Exception('Not implemented.');
	}

	#get a new key value for the given partition column.  
	#by default assign it by hash into the cluster.  You can move keys around later
        #if you implement the move_key function
	public function new_key($column, $shard_name=null) {
		$this->get_shards();

		$column = $this->escape($column);
		$this->execute('BEGIN');

		#generate a new "shared" auto-increment value
		$sql = "INSERT INTO shard_columns (column_name) values ('$column') ON DUPLICATE KEY UPDATE next_value = last_insert_id(next_value+1)";
		$this->execute($sql);
		$id = mysql_insert_id($this->conn);

		if(!$shard_name) {
			$shard_ids = array_keys($this->shards);
			$shard_name =  $shard_ids[ $id % (count($shard_ids)) ] ;
		} else {
			if(!in_array($shard_name, array_keys($this->shards))) {
				throw new Exception('The given shard name has not been added.');
			}

		}

		$shard_name = mysql_escape_string($shard_name);

		$sql = "INSERT INTO shard_map  (column_id, shard_id,key_value) VALUES ( (select column_id from shard_columns where column_name = '$column'),(select shard_id from shards where shard_name = '$shard_name'), $id)";
		$this->execute($sql);
		$this->execute('COMMIT');
	
		return array($shard_name => $id);

	}

	#get all the keys which belong to a particular shard
	public function get_keys($column, $shard_name) {
		$this->connect();
		$sql = "select key_value from shard_map join shard_columns using (column_id) join shards using(shard_id) where column_name = '$column' and shard_name = '$shard_name'";
		$stmt = $this->execute($sql);	
		while($row = mysql_fetch_assoc($stmt)) {
			$keys[] = $row['key_value'];
		}
	
		return $keys;
	}

	public function get_key_distribution($column) {
		$this->connect();
		$sql = "select shard_name,  count(*) cnt from shard_map join shards using(shard_id) join shard_columns using (column_id) where column_name = '$column' GROUP BY shard_name";
		$stmt = $this->execute($sql);	
		while($row = mysql_fetch_assoc($stmt)) {
			$rows[$row['shard_name']] = $row['cnt'];
		}
	
		return $rows;
	}

	#returns an array which maps a shard name to the id value 
	#will create the mapping if it does not exist
	public function map($column, $key) {
		static $cache=array();
		$this->get_columns();

		if(!empty($cache[$column . $key])) return $cache[$column . $key];

		if(!is_numeric($key)) {
			throw new Exception('Only numeric keys are supported');
		}

		$sql = "select shard_name from shard_map join shards using(shard_id) join shard_columns using(column_id) where column_name = '" . $this->escape($column) . "' and key_value = $key";
		$stmt = $this->execute($sql);	

		$row = mysql_fetch_assoc($stmt);

		if(!$row) {
			$info = $this->new_key($column);
			$keys = array_keys($info);
			$cache[$column . $key] = $keys[0];
			return $info;
			
		}

		$cache[$column . $key] = $row['shard_name'];

		return(array($row['shard_name'] => $key));
	
	}

	public function get_columns() {
		$this->connect();
		$sql = "select column_name from shard_columns";
		$stmt = $this->execute($sql);	
		while( $row = mysql_fetch_assoc($stmt) ) {
			$columns[] = $row['column_name'];
		}
		$this->columns = $columns;
		return $columns;
	}
	
}
