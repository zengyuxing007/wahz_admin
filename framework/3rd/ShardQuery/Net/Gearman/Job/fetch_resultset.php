<?php
require_once 'Net/Gearman/Job.php';
#THIS JOB SENDS THE DATA BACK AS AN ARRAY

class Net_Gearman_Job_fetch_resultset extends Net_Gearman_Job_Common
{
    public function run($arg) {
	if(!$arg) return;
	$arg = (object)$arg;
	$arg->shard = (object)$arg->shard;
	$conn = false;
        $conn = @mysql_connect($arg->shard->host, $arg->shard->user, $arg->shard->password,true);
	if(!$conn) throw new Net_Gearman_Job_Exception(mysql_error());

	if(!mysql_select_db($arg->shard->db, $conn)) throw new Net_Gearman_Job_Exception(mysql_error());

    	$stmt = mysql_query($arg->sql, $conn);    
	if(!$stmt) throw new Net_Gearman_Job_Exception(mysql_error());

	$rows = array();
	while($row = mysql_fetch_assoc($stmt)) {
		$rows[] = $row;
	}
	return(array('rows' => $rows, 'err' => NULL, 'errno' => NULL));
    }
}

?>
