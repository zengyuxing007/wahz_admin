<?php
require_once 'Net/Gearman/Job.php';
require_once '../../../include/shard-key-mapper.php';


class Net_Gearman_Job_directory_lookup extends Net_Gearman_Job_Common
{
	public function run($arg) {
		if(!$arg) return;
		$arg = (object)$arg;

		$map = new DirectoryShardKeyMapper($arg->directory);
		
		return $map->map($arg->partition_column, $arg->value);
	}

}

?>
