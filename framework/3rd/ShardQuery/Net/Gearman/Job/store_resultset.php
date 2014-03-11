<?php
#THIS JOB INSERTS DATA DIRECTLY INTO the tmp_shard
#This job also uses an unbuffered query
require_once 'Net/Gearman/Job.php';

function return_error($reason, $where, $info ) {
	if(!empty($reason)) print_r($reason);

	$where=(array)$where;

	$err = "$reason:$info";
	echo $err . "\n";
	return array('error' => $err);
}

class Net_Gearman_Job_store_resultset extends Net_Gearman_Job_Common
{
    public function run($arg) {

	$errors = false;

	$stmt = false;
	if(!$arg) return;
	$arg = (object)$arg;
	$arg->shard = (object)$arg->shard;
	$arg->tmp_shard = (object)$arg->tmp_shard;
	$conn = false;
	if(!empty($arg->shard->port)) $arg->shard->host .= ':' . $arg->shard->port;
        $conn = @mysql_connect($arg->shard->host, $arg->shard->user, $arg->shard->password,true);
	if(!$conn) return(return_error('Failed to connect to storage node:',$arg->shard,mysql_error()));

	if(empty($errors)) {

#		print_r($arg);

		if(!empty($arg->tmp_shard->port)) $arg->tmp_shard->host .= ':' . $arg->tmp_shard->port;
		$tmp_conn = @mysql_connect($arg->tmp_shard->host, $arg->tmp_shard->user, $arg->tmp_shard->password,true);
		if(!$tmp_conn) return(return_error('Failed to connect to coordination node', $arg->tmp_shard, mysql_error($tmp_conn)));

		if(!mysql_select_db($arg->shard->db, $conn)) return(return_error('Could not change to directory', $arg->shard, mysql_error($conn)));
		if(!mysql_select_db($arg->tmp_shard->db, $tmp_conn)) return(return_error('Could not change to directory', $arg->tmp_shard, mysql_error($tmp_conn)));

		$stmt = mysql_query("select @@max_allowed_packet", $tmp_conn);
		if(!$stmt) return(return_error('Could not set max_allowed_packet', $this->tmp_shard, mysql_error()));
		$row = mysql_fetch_array($stmt);
		mysql_free_result($stmt);
		$max_len = $row[0] - 4096;

		$stmt = mysql_query("SET STORAGE_ENGINE=MYISAM", $tmp_conn);

		$sql = "select count(*) cnt from information_schema.tables where table_schema = '" . mysql_real_escape_string($arg->tmp_shard->db) . "' and table_name = '" . $arg->table_name . "'";
		$stmt = mysql_query($sql, $tmp_conn); 
		$row = mysql_fetch_assoc($stmt);
		$table_exists = $row['cnt'] > 0;
		mysql_free_result($stmt);

		#get the data from the shard using MYSQL_USE_RESULT

		#echo "RUNNING:{$arg->sql}\n";
	    	$stmt = mysql_unbuffered_query($arg->sql, $conn);    
		/*if(!$stmt) {
				return(return_error('Error while running unbuffered query:',$arg->shard, mysql_error($conn).'['.mysql_errno($conn).']'));
			}
		}*/

		$created_sql = false;	
	        $sql = "INSERT INTO `{$arg->table_name}` VALUES ";
		$values = "";
		$odku="";
		if(!empty($arg->coord_odku))  {
			$odku=' ON DUPLICATE KEY UPDATE ' . join(",\n",$arg->coord_odku);
			
		}

		while($stmt && $row = mysql_fetch_assoc($stmt)) {
			if(!$created_sql)  {
        	        	
        	        	$created_sql = "CREATE TABLE IF NOT EXISTS {$arg->table_name} (";
		                $col_sql = "";
        		        $cols = array_keys($row);

		                for($i = 0; $i<count($cols);++$i) {
					$use_datatype="VARBINARY(255)";
					switch(mysql_field_type($stmt,$i)) {
						case 'int':
						$use_datatype="BIGINT";
						break;

						case 'real':
						$use_datatype="DECIMAL(20,6)";
						break;
	
						case 'string':
						$use_datatype="VARBINARY(255)";
						break;

						case 'blob':
						case 'default':
						$use_datatype='VARBINARY(255)';
					}

        		                if($cols[$i] == "0") continue;
		                        if($col_sql) $col_sql .= ", ";
		                        $col_sql .= "`{$cols[$i]}` $use_datatype";

		                }
		                $created_sql .= $col_sql; 
        	        	if(!empty($arg->agg_key_cols) && $arg->agg_key_cols) $created_sql .= ",PRIMARY KEY(" . $arg->agg_key_cols . ") ";
				$created_sql .= ") ENGINE=". $arg->engine;
        	        	if(!$table_exists) {
					#echo "CREATE: $created_sql\n";
					if(!mysql_query($created_sql, $tmp_conn)) {
						echo "FAILED: $created_sql: " . mysql_error($tmp_conn) . "\n";
						return(return_error('Error while running query: ' . $created_sql, $this->tmp_shard, mysql_error($tmp_conn)));
					}
				}
			}
        	
			if($values) $values .= ",";
			$val_list = "";
			foreach($row as $col => $val) {
				#echo "COL: $col, VAL: $val\n";
				if($col == "0") continue;
				if($val_list) $val_list .= ',';
				if(is_numeric($val)) {
					 $val_list .= "{$val}";
				} else {
					$val_list .= "'" . mysql_real_escape_string($val) . "'";
				}
			}
			$values .= "({$val_list})";
			#we don't want to exceed max_packet_len
			if(strlen($values) >= $max_len) {
#				echo "INSERT1: $sql $values $okdu \n";
				if(!mysql_query($sql . $values . $odku, $tmp_conn)) {
					echo "FAILED\n";
					return(return_error('Error while inserting: ' . $sql . $values . $odku, $arg->tmp_shard, mysql_error($tmp_conn)));
				}
				$values = "";
			}
		}
		#any rows left over?
		if($values) {
#			echo "INSERT2: $sql $values $okdu \n";
			if(!mysql_query($sql . $values . $odku, $tmp_conn)) {
				return(return_error('Error while inserting: ' . $sql . $values . $odku, $arg->tmp_shard, mysql_error($tmp_conn)));
			}
		}
		if($stmt) mysql_free_result($stmt);
		mysql_close($tmp_conn);
		mysql_close($conn);
	}
    	return(array('done' =>true));
    }
}

?>
