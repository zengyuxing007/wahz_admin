<?php
ini_set('memory_limit', 1024*1024*400);
require_once 'Net/Gearman/Job.php';
require_once 'shard-query.php';
require_once 'shard-key-mapper.php';


class Net_Gearman_Job_shard_query_directory extends Net_Gearman_Job_Common {
	public function run($arg) {
		
		if(!$arg) return false;
		if(empty($arg['sql']) || !trim($arg['sql'])) return false;

		$arg = (object)$arg;
		$fields = false;
		$errors = false;
		$rows = false;
		$sql = "";
		$has_rows = false;
		$resultset = false;
	
		if(preg_match('/select\s+.*\sfrom\s.*/i', $arg->sql)) {

			$SQ = new ShardQuery($map->get_shards(), $arg->params);
			$stmt = $SQ->query($arg->sql);
			if(!empty($SQ->errors)) $errors = trim(str_replace(array("\n","Array","(",")","  "),"",print_r($SQ->errors, true)));
			

			# return the actual object so that the proxy can finish aggregation and drop the table
			if($stmt) {
				$has_rows = true;
				$rows = array(array());
				# get the first row and use it to construct the list of fields + collect row data
				# in this first fetch we process data one output column at a time
				$row = mysql_fetch_assoc($stmt);
				foreach($row as $field => $val) {
					$rows[0][] = $val;
					$fields[] = array( 'type' => 250, 'name' => $field );
				}

				# fetch the rest of the rows numerically and stuff into $rows, a row at a time
				while($row = mysql_fetch_array($stmt,MYSQL_NUM)) $rows[] = $row;

				$resultset = array('fields' => &$fields, 'rows'=>&$rows);
			}# else {
			#	$sql = "select 'no resultset'";
			#}
		} else {
			$sql = $arg->sql;
		}

		return json_encode(array('resultset' => $resultset, 'errors'=>$errors, 'sql' => $sql, 'has_rows' => $has_rows));
	}
}

?>
