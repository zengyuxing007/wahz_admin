<?php
require_once 'Net/Gearman/Job.php';
require_once '../../../include/shard-query.php';


class Net_Gearman_Job_hash_lookup extends Net_Gearman_Job_Common
{
	public function run($arg) {
		if(!$arg) return;
		$arg = (object)$arg;

		$map = new HashShardKeyMapper($arg->shards);
		$map->add_column($arg->partition_column);
		
		return $map->map($arg->partition_column, $arg->value);
	}

}

?>
