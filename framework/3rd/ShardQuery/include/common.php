<?php
/*This script requires PEAR Net_Gearman */
/*It also requires Console_Getopt, but this should be installed by default with pear */
require_once 'Console/Getopt.php';
$SHARD_QUERY_PATH=dirname(__FILE__)."/../";

function &get_commandline($more_longopts=array()) {
	$cg = new Console_Getopt();
	$args = $cg->readPHPArgv();
	array_shift($args);

	$shortOpts = 'h::v::';
	$longOpts  = array('user=','ini=', 'password=', 'host=', 'db=', 'port=','help==','verbose==', 'method=', 'gearman=','inlist=','between=','ignore_shard=','directory=','shared=','push_where','inlist_merge_threshold==','inlist_merge_size==','coord_engine==');

	$longOpts = array_merge($longOpts, $more_longopts);

	$params = $cg->getopt2($args, $shortOpts, $longOpts);
	if (PEAR::isError($params)) {
	    echo 'Error: ' . $params->getMessage() . "\n";
	    exit(1);
	}
	$new_params = array();
	foreach ($params[0] as $param) {
		$param[0] = str_replace('--','', $param[0]);
		$new_params[$param[0]] = $param[1];
	}

	if(empty($new_params['ignore_shard'])) $new_params['ignore_shard'] = 'directory';

	unset($params);
	return $new_params;
}

function get_shards($params, $return_defaults=false) {
   
	$shards = array();

	if(!empty($params['ini'])) {
		$filename = $params['ini'];
	} else { 
		$filename = ROOT_PATH . "/config/shards.ini";
	}
	
	if(!file_exists($filename)) {
		die("Could not find settings file: $filename (use genconfig)\n");
	}
	$shards = @parse_ini_file($filename, true);

	if(!empty($shards['default'])) {
		if($return_defaults) {
			return $shards['default'];
		}
		$defaults = $shards['default'];

		unset($shards['default']);
	} else {
		$defaults = array('host' => '127.0.0.1', 'port'=>3306, 'user'=> 'root', 'db' => 'test','password' => '');
	}


	foreach($defaults as $key => $val) {
		if(!empty($params[$key])) $defaults[$key] = $params[$key];
	}

	
	$shard_keys = array_keys($shards);
	
	#populate missing values with the defaults, in case any INI sections are missing things like 'db'
	for($i=0;$i<count($shard_keys);++$i) {
		foreach($defaults as $key => $val) {
			if (empty($shards[$shard_keys[$i]][$key]) || $shards[$shard_keys[$i]][$key] == '') {
				$shards[$shard_keys[$i]][$key] = $val;
			}
		}

		if(empty($shards[$shard_keys[$i]]['db'])) $shards[$shard_keys[$i]]['db'] = $shard_keys[$i];
	}

	return $shards;
}

function get_defaults($params) {
	return(get_shards($params,true));
}

function has_short_opt($needle,$haystack) {
	if(!is_array($needle)) {
		$needle=array($needle);
	}

	foreach ($needle as $opt) {
		if(in_array($opt,array_keys($haystack))) return true;
	}

	return false;
}

function run_query_help() {
	echo "\nrun query options:\n";
	echo "----------------------\n";
	echo "--file            Full path to the file to read queries from.  If not specified, then queries are read from stdin\n";
}

function loader_help() {
	echo "\nloader options:\n";
	echo "----------------------\n";
	echo "--spec		Full path to the .spec file (loader specfication file)\n";
	echo "--child-file	Full path to th file to load (reserved option)\n";
	echo "--child-start	Byte offset at which to start reading (reserved option)\n";
	echo "--child-stop	Byte offset at which to stop reading (reserved option)\n";
}

function genspec_help() {
	echo "\ngenspec options:\n";
	echo "----------------------\n";
	echo "--db 		Comma separated list of databases (or just one) to create spec file for\n";
	echo "--user		User to connect as\n";
	echo "--password	Password to connect with\n";
	echo "--host		Host to connect to\n";
	echo "--port		Port to connect to\n";
	echo "--delimiter	Delimiter to use\n";
	echo "--skip		Number of rows to skip\n";
	echo "--enclosure	Enclosure to use\n";
	echo "--workers	Number of workers to invoke\n";
	echo "--path		Path where the input files are located\n";
	
}
	

function shard_query_common_help($exit=false) {
	echo "common options:\n";
	echo "----------------------\n";
	echo "--help            This message\n";
	echo "--ini             Full path to the .ini file used for shard information.  Defaults to shards.ini in the working directory\n";
	echo "--verbose Print verbose information about the work being done\n";
	echo "\n--method=fetch|store    fetch workers fetch the results and return them as an array.\n";
	echo "                  store workers insert results directly into one of the shards. store is more parallel\n";
	echo "                  The default method is store, and only up to max_allowed_packet bytes will be buffered in the worker.\n";
	echo "\n--inlist=column_name|*  You can specify a particular column for inlist parallelization, or you can parallelize all inlist combinations\n";
	echo "\n--between=column_name|*  You can specify a particular column for between parallelization, or you can parallelize all between operations (this optimization will detect the expression type and will only operate on integer and date literals)\n";
	echo "                          example: --inlist=my_column, and the query contains WHERE my_column IN (1,2,3)\n";
	echo "                          Three queries will be queued to the shards: WHERE my_column = 1, WHERE my_column = 2, WHERE my_column = 3\n";
	echo "                          example: --inlist=*, with WHERE col1 IN (1,2) AND col2 in (2,3)\n";
	echo "                          Four queries are queued: col1 = 1 and col2 = 2, col1 = 1 and col2 = 3, col1 = 2 and col2 = 2, col1 = 2 and col2 = 3\n";
	echo "--gearman=host:port[,host:port]...        The gearman server to submit jobs to.\n";
	echo "--inlist_merge_threshold=#    If an IN list contains at least this many items, then it will be split up into small lists of --inlist_merge_size IN lists (defaults to 128)\n";
	echo "--inlist_merge_size=#        When between is merged to IN list, this many items will be merged into a single IN (defaults to 128) .\n";
	echo "--coord_engine=MYISAM|INNODB|MEMORY       The storage engine to use on the coordinator node\n";
	echo "--max-degree	Maximum degree of parallelism to employ when running SELECT queries\n";
	
	echo "\n\nNOTE:\n";
	echo "Any parameters in a particular [shard] section override any default parameters.\n\n";
	echo "Command line parameters have the highest priority and will override both default and INI settings\n\n";
	if($exit) exit;
}

