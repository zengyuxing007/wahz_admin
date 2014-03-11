<?php
/*This script requires PEAR Net_Gearman */
/*It also requires Console_Getopt, but this should be installed by default with pear */
require_once 'Net/Gearman/Client.php';
require_once 'simple-dal.php';
require_once 'mysql.php';
require_once 'php-sql-parser.php';
require_once 'shard-key-mapper.php';
#$params = get_commandline();

#FIXME: This should not extend MySQLDAl, but should create a new DL object
#DL needs additions such as creating table, check existence of table, etc.
#This will be useful for FlexCDC and Shard-Query.
class ShardQuery extends MySQLDAL {
	var $messages = array();
	var $push_where="";
	var $inlist_merge_threshold = 128;
	var $inlist_merge_size = 128;
	var $engine='MEMORY';

	var $cols;
	var $debug;
	var $fetch_method;
	var $gearman_servers;
	var $inlist_opt;
	var $row_count;
	var $shown_temp_table_create;
	var $table_name;
	var $tmp_shard;
	var $verbose;

	var $conn = null;

	var $workers; # the count of workers

	var $force_shard = array();

	var $error = false;

	var $partition_callback = false;
	var $partition_column = false;

	var $coord_odku = array();

	var $subqueries = array();

	#this function works only with SELECT statements and it only works with a single query
	#the query may be parallelized based on the --inlist, --between options and the number of shards
	function query($sql) {
		
		$start = microtime(true);
		$this->conn = $this->my_connect($this->tmp_shard);
		
		if(!$this->conn) {
			throw new Exception("connect to the coordinator DB:" . $this->my_error() . "\n");
		}
		
		if(!$this->my_select_db($this->tmp_shard['db'], $this->conn)) {
			throw new Exception("use the coord db database:" . $this->my_error($this->conn));
		} 
		$this->workers=0;

		$this->client = new Net_Gearman_Client($this->gearman_servers) or die('Could not instantiate gearman client\n');

		$this->subqueries = array();

		

		if(!$this->process_sql($sql)) {
			echo "AN ERROR OCCURRED WHILE PROCESSING THIS SQL:\n";
			print_r($this->errors);
			return false;	
		}

		$this->total_time = $this->parse_time = microtime(true) - $start;
		$start = microtime(true);

		$collect_cb = array($this,'collect_rowset');
		$failure_cb = array($this,'handle_failure');

		#subqueries in the from clause get parallelized on each storage node

		if(count($this->subqueries) > 0 ) {
			if($this->verbose) {
				$this->messages[] = "Shard-Query has detected" . count($this->subqueries) . " subqueries in the FROM clause\n";
			}
		}

		$set = new Net_Gearman_Set();
		if(!empty($this->subqueries)) {
			$save_agg_keys = $this->agg_key_cols;
			$save_odku = $this->coord_odku;
			$this->agg_key_cols = "";
			$this->coord_odku=array();
			foreach($this->subqueries as $table => $subsql) {
				#last param is a reference, so this will populate the set
				$this->create_gearman_set($subsql, $table, $collect_cb, $failure_cb,$set);
			}
			$this->agg_key_cols = $save_agg_keys;
			$this->coord_odku=$save_odku;

		}

		$this->run_set($set);
		$msg = 0;
		if($this->workers>0 ) {
			sleep(.0005);
		}

		if($this->errors) {
			print_r($this->errors);
			return false;
		} 

		$set = new Net_Gearman_Set();
		$this->workers=0;

		$this->conn = $this->my_connect($this->tmp_shard);
		$this->create_gearman_set($this->shard_sql, $this->table_name, $collect_cb, $failure_cb,$set);
		$this->run_set($set);
		$spins=0;

		while($this->workers>0) {
			sleep(.0005);
		}

		if($this->errors) {
			print_r($this->errors);
			return false;
		} 

		

		$stmt = $this->aggregate_result();
		$this->messages=array();

		$this->exec_time = microtime(true) - $start;
		$this->total_time += $this->exec_time;

		mysql_close($this->conn);

		return $stmt;
		
	}

	#this function broadcast one or more queries of any type to all shards
	function broadcast($queries,$collect_cb=null,$failure_cb=null) {
		if(!$collect_cb) $collect_cb=array($this,'generic_rowset');
		if(!$failure_cb) $failure_cb=array($this,'handle_failure');
		$start = microtime(true);
		$this->coord_sql = false;
		$this->force_shard = false;
		$this->table_name = false;
		$save_fetch_method = $this->fetch_method;
		
		$this->errors = false;	
		$result = $this->run_set($this->create_gearman_set($queries, false, $collect_cb, $failure_cb));
		$this->fetch_method = $save_fetch_method;
		$this->exec_time = microtime(true) - $start;
		return $result;
		
	}

	function __construct($shards, $params=array()) {
		$this->verbose=false;
		$this->fetch_method = "store_resultset";
		$this->inlist_opt=false;
		$this->between_opt=false;
		$this->gearman_servers = array();
		$this->debug = false;
		$this->row_count = 0;
		$this->tmp_shard = array();
		$this->max_degree = false;

		$shared = false;

		if(in_array('verbose', array_keys($params))) {
			$this->verbose = true;
		}

		$this->max_degree = count($shards) * 2;

		if(!empty($params['max_degree'])) $this->max_degree = $params['max_degree'];
	
		#merge any explicit parameters with the defaults and/or INI options
		$params = array_merge(get_shards($params, true), $params);

		if(!empty($params['mapper']) && $params['mapper'] == 'directory') {
			if(empty($params['directory'])) $params['directory'] = 'directory';
			if(!empty($params['ignore_shard'])) $params['ignore_shard'] .= ',';
			$params['ignore_shard'] .= 'directory';
		}
			
		if(!empty($params['shared'])){
			$shared = $shards[$params['shared']];
			if(!empty($params['ignore_shard'])) $params['ignore_shard'] .= ',';
			$params['ignore_shard'] .= $params['shared'];
		}

		if(!empty($params['push_where'])) {
			$this->push_where=$params['push_where'];
		}

		if(!empty($params['inlist_merge_threshold'])) {
			$this->inlist_merge_threshold=$params['inlist_merge_threshold'];
		}

		if(!empty($params['inlist_merge_size'])) {
			$this->inlist_merge_size=$params['inlist_merge_size'];
		}

		if(!empty($params['coord_engine'])) {
			$this->engine=$params['coord_engine'];
		}

		#unset any shards we want to ignore (normally just directory)
		if(!empty($params['ignore_shard'])) {
			$ignore = explode(',',$params['ignore_shard']);
			foreach($ignore as $shard) {
				unset($shards[$shard]);
			}
		}

		$mapper=false; 
		if(!empty($params['mapper'])) {
			switch ($params['mapper']) {
				case 'hash':
					$mapper = new HashShardKeyMapper($shards);
					$this->shared=$shared;
				break;

				case 'directory':
					$mapper = new DirectoryShardKeyMapper($directory);
					$shards = array_merge($shards,$map->get_shards());
					$this->shared=$shared;
				break;

				default:
				break;
			}

			if(!empty($params['column'])) $this->set_partition_info($params['column'], array($mapper,'map'));
		}


		if(!empty($params['chunk'])) $this->chunk = $params['chunk']; else $this->chunk = false;
	
		$this->shard_ids = array_keys($shards);
		$this->shards = $shards;
		/* One shard acts as the coordination shard.  A table (or a temporary table) is created on this shard
		   to coallesce the results from the other shards.  This shard is used when a query broadcast
		   is necessary, otherwise only one shard will be used.
		*/
		if(!empty($params['coord_shard'])) {
			if(is_array($params['coord_shard'])) {
				$this->tmp_shard = $params['coord_shard'];
			} else {
				$this->tmp_shard = $shards[$params['coord_shard']];
			}
		} else {
			#pick a random shard
			$this->tmp_shard = $shards[array_rand($shards)];
		}
	
		if(!empty($params['method'])) {
			if($params['method'] == 'fetch') {
				$this->fetch_method = 'fetch_resultset';
			}
			if($params['method'] == 'store') {
				$this->fetch_method = 'store_resultset';
			}
		}
	
		if(!empty($params['inlist'])) {
			$this->inlist_opt = trim($params['inlist']);
		}

		if(!empty($params['between'])) {
			$this->between_opt = trim($params['between']);
		}

		if(!empty($params['gearman'])) {
			$this->gearman_servers = explode(',', $params['gearman']);
		} else {
			$this->gearman_servers=array('localhost:4730');
		}

	
	}

	#FIXME: Make this support SUM(X) + SUM(Y).
	#To do this create process_select_expr and then iterate (possively recursively) 
	#over all expressions in the select calling that function
	protected function process_select($select, $straight_join=false) {
		$error = array();	
		$shard_query=""; 		#Query to send to each shard
		$coord_query=""; 		#Query to send to the coordination node
	
		$avg_count = 0;
		
		$group = array();		#list of positions which contain non-aggregate functions
		$push_group=array();  	#this is necessary for non-distributable aggregate functions
		$group_aliases=array(); #list of group aliases which will be indexed on the aggregation temp table
						  
		$is_aggregate = false;
		$coord_odku=array();

		
		$used_agg_func=0;

		foreach($select as $pos => $clause) {
			if($shard_query) $shard_query .= ",";	
			if($coord_query) $coord_query .= ",";	
		

			if(!empty($clause['base_expr']) && $clause['base_expr'] == "*") {
				$error[] = array('error_clause' => '*', 'error_reason'=>'"SELECT *" is not supported');
				continue;

			}
			$alias = $clause['alias'];

			if(strpos($alias,'.')) {
				$alias=trim($alias,'``');
				$alias = explode('.', $alias);
				$alias = $alias[1];
				$alias="`$alias`";
			}

			$base_expr = $clause['base_expr']; 

			switch($clause['expr_type']) {
				case 'expression':
					if($clause['sub_tree'][0]['expr_type'] == 'aggregate_function') {
						$is_aggregate = true;
						$skip_next = true;
						$base_expr = $clause['sub_tree'][1]['base_expr'];
						$alias = $clause['alias'];
						$function = $clause['sub_tree'][0]['base_expr'];
					
						switch($function) {
							case 'MIN':
							case 'MAX':
							case 'SUM':
								$used_agg_func=1;
								$expr_info=explode(" ", $base_expr);
								if(!empty($expr_info[0]) && strtolower($expr_info[0]) == 'distinct') {
										
										if($this->verbose) {
											echo "Detected a {$function} [DISTINCT] expression!\n";
										}
										
										unset($expr_info[0]);
										$new_expr = join(" ", $expr_info);
										$shard_query .= "$new_expr AS $alias";
										$coord_query .= "{$function}(distinct $alias) as $alias";	
										$push_group[] = $pos + 1;								
								} else {
									switch($function) {
										case 'SUM':
											$coord_odku[] = "$alias=$alias +  VALUES($alias)";
											break;
										case 'MIN':
											$coord_odku[] = "$alias=IF($alias < VALUES($alias), VALUES($alias),$alias)";
											break;
										case 'MAX':
											$coord_odku[] = "$alias=IF($alias > VALUES($alias), VALUES($alias), $alias)";
											break;
									}
									$shard_query .= "{$function}({$base_expr}) AS $alias";		
									$coord_query .= "{$function}({$alias}) AS $alias";
								}
								
							break;
							
							case 'AVG':								
							case 'STDDEV':
							case 'STD':
							case 'STDDEV_POP':
							case 'STDDEV_SAMP':
							case 'VARIANCE':
							case 'VAR_POP':
							case 'VAR_SAMP':
							case 'GROUP_CONCAT':
								$used_agg_func=1;
								$expr_info=explode(" ", $base_expr);
								if(!empty($expr_info[0]) && strtolower($expr_info[0]) == 'distinct') {
										if($this->verbose) {
											echo "Detected a {$function} [DISTINCT] expression!\n";
										}
										
										unset($expr_info[0]);
										$new_expr = join(" ", $expr_info);
										$shard_query .= "$new_expr AS $alias";
										$coord_query .= "{$function}(distinct $alias) as $alias";
								} else {
										$shard_query .= "({$base_expr}) AS $alias";		
										$coord_query .= "{$function}({$alias}) AS $alias"; 
								}
								$push_group[] = $pos + 1;
								$group_aliases[] = $alias;
								

							break;

							case 'COUNT':
								$used_agg_func=1;
								$base_expr=trim($base_expr,' ()');

								$expr_info=explode(" ", $base_expr);
								if(!empty($expr_info[0]) && strtolower($expr_info[0]) == 'distinct') {
									if($this->verbose) {
										echo "Detected a COUNT [DISTINCT] expression!\n";
									}
									unset($expr_info[0]);
									$new_expr = join(" ", $expr_info);
									$shard_query .= "$new_expr AS $alias";
									$coord_query .= "COUNT(distinct $alias) as $alias";
									$push_group[] = $pos + 1;
									
								} else {
									$shard_query .= "COUNT({$base_expr}) AS $alias";
									$coord_query .= "SUM($alias) AS $alias";
									$coord_odku[] = "$alias=$alias +  VALUES($alias)";
								}
	
							break;
		
							default:
								$error[] = array('error_clause' => $clause['base_expr'],
										'error_reason' => 'Unsupported aggregate function');
										
				
							break;
						}
					} else {
						$group[] = $pos+1;
						$group_aliases[] = $alias;
		
						$shard_query .= $base_expr . ' AS ' . $alias;
						$coord_query .= $alias;
						$coord_odku[] = "$alias=VALUES($alias)";
					}
			
				break;
	
				case 'operator':
				case 'const':
				case 'colref':
				case 'reserved':
				case 'function':
					$group[] = $pos+1;
					$group_aliases[] = $alias;
					$shard_query .= $base_expr . ' AS ' . $alias;
					$coord_query .= $alias;
					$coord_odku[] = "$alias=VALUES($alias)";
			
				break;
	
				default:
					$error[] = array('error_clause' => $clause['base_expr'],
							'error_reason' => 'Unsupported expression type (did you forget an alias on an aggregate expression?)');
				break;
			}	
			
		}

		$sql = "SELECT ";
		if($straight_join) $sql .= "STRAIGHT_JOIN ";

		$sql .= $shard_query;
	
		$shard_group=array();	
		#merge pushed and provided group-by
		if($used_agg_func) {
			$shard_group = $group;
			foreach($push_group as $push) {
				$shard_group[]=$push;
			}
			#they shouldn't conflict, but this ensures so
			$shard_group = array_unique($shard_group);
		} else {
			$group = array(); $shard_group = array();
		}
		

		#we can't send pushed group by to the coord shard, so send the expression based 
		return array('error'=>$error,'shard_sql'=>$sql, 'coord_odku' => $coord_odku, 'coord_sql'=> 'SELECT ' . $coord_query,'shard_group' => join(',',$shard_group), 'coord_group'=>join(',',$group), 'group_aliases' => join(',',$group_aliases));
	}
	
	protected function process_from($tables) {

		/* DEPENDENT-SUBQUERY handling
		*/

		foreach($tables as $key => $table) {
			if($table['table'] == 'DEPENDENT-SUBQUERY') {
				$this->process_sql($table['sub_tree']);
				$this->subqueries[$this->table_name] = $this->shard_sql;

				$sql = '(' . $this->coord_sql . ')';
				$tables[$key]['table'] = $sql;

			}
		}

		#escape the table name if it is unescaped
		if($tables[0]['alias'][0] != '`' && $tables[0]['alias'][0] != '(') $tables[0]['alias'] = '`' . $tables[0]['alias'] . '`';

		#the first table is always prefixed by FROM
		$sql = "FROM " . $tables[0]['table'] . ' AS ' . $tables[0]['alias'];
		$cnt = count($tables);

		#now create the rest of the FROM clause
		for($i=1;$i<$cnt;++$i) {

			if($tables[$i]['ref_type'] == 'USING') {
				$tables[$i]['ref_clause']="(" . trim($tables[$i]['ref_clause']) . ")";
			} elseif($tables[$i]['ref_type'] == 'ON') {
				$tables[$i]['ref_clause'] = ' (' . $tables[$i]['ref_clause'] . ") ";
			}

			if($sql) $sql .= " ";
			if($tables[$i]['alias'][0] != '`' && $tables[$i]['alias'][0] != '(') {
				$pos = strpos($tables[$i]['alias'], '.');
				if($pos !== false) {
					$info = explode('.', $tables[$i]['alias']);
					$table = $info[1];
					$tables[$i]['alias'] = '`' . $table . '`';	
				} else {
					$tables[$i]['alias'] = '`' . $tables[$i]['alias'] . '`';	

				}
			}
			$sql .= $tables[$i]['join_type'] . ' ' . $tables[$i]['table'] . ' AS ' . $tables[$i]['alias'] . ' ' . $tables[$i]['ref_type'] . $tables[$i]['ref_clause'];

		}
	
		return $sql;
	}

	function set_partition_info($column, $callback) {
		$this->partition_column = $column;
		if(!is_callable($callback)) {
			throw new Exception('Invalid callback (is_callable failed)');
		}

		$this->callback = $callback;
	}

	protected function get_partition_info($column, $key) {
		$result = call_user_func($this->callback, $column, $key);
	#	if($this->verbose) {
	#		echo "PARTITION LOOKUP: $column, $key => $result\n";
	#	}

		if(is_array($result)) {
			$keys=array_keys($result);
			$result=$keys[0];
		}
		return $result;
	}

	protected function append_all(&$queries, $append) {
		for($i=0;$i<count($queries);++$i) {
			$queries[$i] .= $append;
		}
	}

	protected function process_where($where) {
		$this->in_lists = array();
		$prev = "";
		$next_is_part_key = false;
		$this->force_shard = false;
		$shard_id = false;
		$this->force_broadcast = false;
		$total_count=0;

		$sql = "WHERE";
		$queries = array($sql);

		$start_count=count($where);
		foreach($where as $pos => $clause) {
			if(empty($where[$pos])) continue;
			$sql .= " ";
			$this->append_all($queries, " ");
			if($next_is_part_key) {
				if(!trim($clause['base_expr'])) continue;
				if($clause['expr_type'] == 'const' && $shard_id = $this->get_partition_info($prev, $clause['base_expr'])) {
					if($this->verbose) echo "PARTITION SELECTION SELECTED SHARD_ID: $shard_id\n";
					$this->force_shard = $shard_id;
				}
				$next_is_part_key = false;
			}

			if ($clause['expr_type'] == 'operator')  {
				if(strtolower($clause['base_expr']) == 'between' &&
				$this->between_opt && ($this->between_opt == '*' || $this->between_opt == $prev) ) {
					$offset=0;
					$operands=array();
					#find the operands to the between expression	
					$and_count=0;

					for($n=$pos+1;$n<$start_count;++$n) {
						if($where[$n]['expr_type'] == 'operator' && strtoupper($where[$n]['base_expr']) == 'AND') {
							if($and_count) {
								break;
							} else {
								$and_count+=1;
							}
						} 
						$operands[$offset]=array('pos'=>$n,'base_expr' => $where[$n]['base_expr']);
						++$offset;
					}

					#determine what kinds of operands are in use
					$matches = $vals = array();
					$is_date = false;

					if(is_numeric(trim($operands[0]['base_expr'])) ||
					 preg_match("/('[0-9]+-[0-9]+-[0-9]+')/", $operands[0]['base_expr'], $matches)) {
						if($matches) {
							$vals[0] = $matches[0];
							$matches = array();
					 		preg_match("/('[0-9]+-[0-9]+-[0-9]+')/", $operands[2]['base_expr'], $matches);
							$vals[1] = $matches[0];
			
							$is_date =true;
						} else {
							$vals[0]  = $operands[0]['base_expr'];
							$vals[1]  = $operands[2]['base_expr'];

						}
						if(!$is_date) {
							$sub_tree = array();
							for($n=$vals[0];$n<=$vals[1];++$n) {
								$sub_tree[] = $n;
							}

						} else {

							#conversion of date between requires connecting
							#to the database to make sure that the date_diff calculation
							#is accurate for the timezone in which the database servers are
						
							$date_sql = "SELECT datediff(" . $vals[1] . ',' . $vals[0] . ") as `d`";
							if($this->verbose) {
								echo "Sending SQL to do date calculation:\n$date_sql\n\n";
							}

							$stmt = $this->my_query($date_sql);
							if(!$stmt) {
								throw new Exception("While doing date diff: " . $this->my_error($this->conn));
							}

							$row = mysql_fetch_assoc($stmt);
							$days = $row['d'];
							for($n=0;$n<=$days;++$n) {
								$sub_tree[] = $vals[0] . " + interval $n day";
							}
						}

						for($n=$pos+1;$n<=$operands[2]['pos'];++$n) {
							unset($where[$n]);
						}

						if($this->verbose) {
							$this->messages[] = "A BETWEEN has been converted to an IN list with " . count($sub_tree) . " items\n";
						}
						$this->in_lists[] = $sub_tree;
						$old = $queries;

						$queries = array("");
						$ilist = "";
						$sub_tree=array_values($sub_tree);

						if(count($sub_tree) >= $this->inlist_merge_threshold ) {
							for($z=0;$z<count($sub_tree);++$z) {
								if($ilist) $ilist .= ",";
								$ilist .= $sub_tree[$z];
								if((($z+1) % $this->inlist_merge_size)  == 0 ) {
									foreach($old as $sql) {
										$queries[] = $sql . " IN (" . $ilist . ")";
									}
									$ilist= "";
								}
							}
							foreach($old as $sql) {
								if($ilist) $queries[] = $sql . " IN (" . $ilist . ")";
							}
							$ilist= "";
						} else {
							foreach($sub_tree as $val) {
								foreach($old as $sql) {
									$queries[] = $sql .= " = $val";
								}
							}	
						}

						unset($sub_tree);

						continue;
					} else {
						if($this->verbose) {
							echo "BETWEEN could not be optimized - invalid operands\n";
						}
					}

				} elseif($clause['base_expr'] == '=' &&
					($this->partition_column && strtolower($this->partition_column) == strtolower($prev) && !$this->force_broadcast ))
			 	{
					if(!$this->force_shard) {
						$next_is_part_key=true;
					} else {
						if($this->verbose) {
							echo "More than one partition key found.  Query broadcast forced\n";
						}
						$this->force_shard = false;
						$this->force_broadcast = true;
					}
				}
				$this->append_all($queries, $clause['base_expr']);
			} elseif($clause['expr_type'] != 'in-list') {
				$this->append_all($queries, $clause['base_expr']);
				$prev = $clause['base_expr'];

			} elseif($this->inlist_opt && ($this->inlist_opt == '*' || $this->inlist_opt == $prev)) {
				$old = $queries;
				$queries=array();
				
				foreach($clause['sub_tree'] as $vals) {

					foreach($old as $sql) {
						$queries[] = "$sql ($vals) ";
					}
				}	

			} else {
				$prev = $clause['base_expr'];
				$this->append_all($queries, $prev);
			}
			
		}

		foreach($queries as $pos => $q) {
			if(!trim($q)) unset($queries[$pos]);
		}

		return array_values($queries);
	}

	/* if $sql is an Array(), then it is assumed it is already parsed */	
	protected function process_sql($sql) {
		#only useful for the fetch worker for debugging	
		$this->shown_temp_table_create = false;
		$this->sql = $sql;
		$parser=null;
		$straight_join = false;
	
		$conn = false;
		
		$this->shard_sql = ""; #identical SQL which will be broadcast to all shards
		$this->coord_sql = ""; #the summary table sql
		$this->in_lists=array()	;
		$error = array();
	
		$select = null;
				
		if(!is_array($sql)) {
			#TODO: support parser re-use	
			#$this->parsed = $this->client->do('sql_parse',$sql);
			$parser = new PHPSQLParser($sql);	

			$this->parsed = $parser->parsed;
		} else {
			$this->parsed = $sql;
		}

		if(!empty($this->parsed['UNION ALL'])) {
			$queries = array();
			foreach($this->parsed['UNION ALL'] as $sub_tree) {
				$this->process_sql($sub_tree);
				$queries = array_merge($queries,$this->shard_sql);
			}	
			$this->table_name = "aggregation_tmp_" . mt_rand(1, 100000000);
			$coord_sql = "SELECT * from " . $this->table_name;
		} elseif(!empty($this->parsed['UNION'])) {
			$queries = array();
			foreach($this->parsed['UNION'] as $sub_tree) {
				$this->process_sql($sub_tree);
				$queries = array_merge($queries,$this->shard_sql);
			}	
			$this->table_name = "aggregation_tmp_" . mt_rand(1, 100000000);
			
			#UNION operation requires deduplication of the temporary table
			$coord_sql = "SELECT DISTINCT * from " . $this->table_name;
		} elseif(!empty($this->parsed['SELECT'])) {
			#reset the important variables	
			$select = $from = $where = $group = $order_by = "";
			$this->errors=array();
			
			#we only support SQL_MODE=ONLY_FULL_GROUP_BY, and we build the GROUP BY from the SELECT expression
			unset($this->parsed['GROUP']);
			
			#The SELECT clause is processed first.
			if(!empty($this->parsed['OPTIONS']) && in_array('STRAIGHT_JOIN', $this->parsed['OPTIONS'])) { 
				$straight_join=true;
				unset($this->parsed['OPTIONS']);
			}

			$select = $this->process_select($this->parsed['SELECT'], $straight_join);

			if(!empty($select['error'])) {
				$this->errors = $select['error'];
				return false;
			}

			unset($this->parsed['SELECT']);

			if(empty($this->parsed['FROM'])) {
				$this->errors = array('Unsupported query', 'Missing FROM clause');
				return false;
			} else {
				$select['shard_sql'] .= "\n" . $this->process_from($this->parsed['FROM']);
				$this->table_name = "aggregation_tmp_" . mt_rand(1, 100000000);
	
				#we only select from a single table here	
				$select['coord_sql'] .= "\nFROM `$this->table_name`";
	
				unset($this->parsed['FROM']);
			}
/*Array
(
    [0] => Array
        (
            [expr_type] => colref
            [base_expr] => date_id
            [sub_tree] => 
        )

    [1] => Array
        (
            [expr_type] => operator
            [base_expr] => between
            [sub_tree] => 
        )

    [2] => Array
        (
            [expr_type] => const
            [base_expr] => 1
            [sub_tree] => 
        )

    [3] => Array
        (
            [expr_type] => operator
            [base_expr] => and
            [sub_tree] => 
        )

    [4] => Array
        (
            [expr_type] => const
            [base_expr] => 2
            [sub_tree] => 
        )

)
*/	

			if($this->push_where !== false && $this->push_where) {
				if(!empty($this->parsed['WHERE'])) {
					$this->parsed['WHERE'][]=array('expr_type'=>'operator','base_expr' => 'and','sub_tree' => "");
				}
				if(!$parser) $parser = new PHPSQLParser();
				$this->messages[] = "Where clause push detected.  Pushing additional WHERE condition:'" . $this->push_where . "' to each storage node.\n";
				if($this->push_where) foreach($parser->process_expr_list($parser->split_sql($this->push_where)) as $item) $this->parsed['WHERE'][]=$item;

			} 

			#note that this will extract inlists and store them in $this->in_lists (if inlist optimization is on) 	
			if(!empty($this->parsed['WHERE'])) {
				$where_clauses = $this->process_where($this->parsed['WHERE']);
				unset($this->parsed['WHERE']);
			}


			if(!empty($this->parsed['ORDER'])) {
				$order_by = "";
				foreach($this->parsed['ORDER'] as $o) {
					if($order_by) $order_by .= ",";
					$order_by .= $o['base_expr'] . ' ' . $o['direction'];	
				}

				
				$order_by = "ORDER BY {$order_by}";
			
				unset($this->parsed['ORDER']);
			}

			if(!empty($this->parsed['LIMIT']) ) {
				$order_by .= " LIMIT {$this->parsed['LIMIT']['start']},{$this->parsed['LIMIT']['end']}";
				unset($this->parsed['LIMIT']);
			}

			

	
			foreach($this->parsed as $key => $clauses) {
				$this->errors[] = array('Unsupported query',$key . ' clause is not supported');
			}

	
			if($this->errors) {
				return false;
			}

			#process_select only provides a list of positions
			if($select['coord_group']) $select['coord_group'] = ' GROUP BY ' . $select['coord_group'];
			if($select['shard_group']) $select['shard_group'] = ' GROUP BY ' . $select['shard_group'];
			
			$queries = array();
			if(!empty($where_clauses)) {
				foreach($where_clauses as $where) {
					$queries[] = $select['shard_sql'] . ' ' . $where . ' ' . $select['shard_group'] . ' ORDER BY NULL';
				}
			} else {
				$queries[] = $select['shard_sql'] . $select['shard_group'] . ' ORDER BY NULL';
			}



		} else {
			
				$this->errors = array('Unsupported query', 'Missing expected clause:SELECT');
				return false;
				
		}

		#if($order_by == "") $order_by = "ORDER BY NULL";
		
		$this->coord_sql = $select['coord_sql'] . ' ' . $select['coord_group'] . ' ' . $order_by;
		$this->coord_odku = $select['coord_odku'];
		$this->shard_sql = $queries;
		$this->agg_key_cols = $select['group_aliases'];
		
		if ($this->verbose) {
			echo "-- INPUT SQL:\n$sql\n";
			echo "\n--PARALLEL OPTIMIZATIONS:\n";


			if($this->agg_key_cols) { 
				echo "\n* The following projections were selected for a UNIQUE CHECK on the storage node operation:\n{$this->agg_key_cols}\n";
			        if($this->coord_odku) echo "\n* storage node result set merge optimization enabled:\nON DUPLICATE KEY UPDATE\n" . join(",\n",$this->coord_odku) . "\n";
			}
			echo "\n";

			foreach($this->messages as $msg) {
				echo "-- $msg\n";
			}

			echo "\n-- SQL TO SEND TO SHARDS:\n";
			print_r($this->shard_sql);

			echo "\n-- AGGREGATION SQL:\n{$this->coord_sql}" . ( $this->agg_key_cols && $this->coord_odku ? "\nON DUPLICATE KEY UPDATE\n" . join(",\n",$this->coord_odku) . "\n" : "\n");
		}

		return true;
	
	}

	protected function create_gearman_set($queries, $table, $complete_cb=null,$failure_cb=null, &$set) {
		$sets = array();
		if(!$set) $set = new Net_Gearman_Set();
		for($i=0; $i<count($queries); ++$i) {
	  		foreach(($this->force_shard != false) ? array($this->shards[$this->force_shard]) : $this->shards as $shard) {
	  			$task = new Net_Gearman_Task($this->fetch_method, array('engine'=>$this->engine,'table_name' => $table, 'sql' => $queries[$i],'coord_odku'=>$this->coord_odku, 'shard'=>$shard,'tmp_shard'=>$this->tmp_shard, 'agg_key_cols'=> $this->agg_key_cols, 'when' => microtime(true)));
				$task->attachCallback($complete_cb,Net_Gearman_Task::TASK_COMPLETE);
				$task->attachCallback($failure_cb,Net_Gearman_Task::TASK_FAIL);
				$set->addTask($task);
			}
			$sets[] = $set;
		}	

		return $sets;
	}

	#runs a set of Gearman workers and waits for them to complete
	protected function run_set($set) { 

		if(!is_array($set)) $set = array($set);

		#start the workers about their work
		$spins = 0;
		$enters = 0;
		$total_sleep_time=0;
		$sleeps=0;
		if($this->verbose) echo "run_set: received another set of jobs\n";
		foreach($set as $the_set) {	
			/* PHP isn't threaded.  When the asynch gearman callback happens, the change in workers is atomic*/
			$this->workers++;
			if($this->verbose) echo "run_set: Starting another gearman job\n";

			$this->client->runSet($the_set);

			/* we have to wait if there are currently $this->max_degree workers running */
			$entered=0;
			while($this->workers >= $this->max_degree) {
				if(!$entered) {
					$entered_at = microtime(true);
					$entered = 1;
					++$enters;
				}

				#we waste up to 2 seconds of wall time here, before we start sleeping for .01 seconds each spin
				if((($start_sleep = microtime(true)) - $entered_at) >= 2.0) {
					++$sleeps;
					sleep(.01);
					$total_sleep_time += microtime(true) - $start_sleep;
				} else {
					++$spins;
				}
				
			} 
			
		}

		if($this->verbose) echo "run_set: finished set execution.  enters: $enters, spins: $spins, sleeps: $sleeps, total_sleep_time: $total_sleep_time\n";

	}

	protected function aggregate_result() {
	
		if($this->debug) {
			$stmt = $this->my_query( "select * from `{$this->table_name}`", $this->conn);
			if(!$stmt) throw new Exception($this->my_error() . "\n");
			while($row = $this->my_fetch_assoc($stmt)) {
				print_r($row);
			}
		}
		
		#Now that the workers have completed, we have to send the final query
		#that returns the accumulated result

		$this->my_select_db($this->tmp_shard['db'], $this->conn) or die($this->my_error($this->conn));

		$stmt = $this->my_query($this->coord_sql,$this->conn);

		if($stmt){
			$sql = "DROP TABLE {$this->table_name};";
			if(!$this->my_query($sql, $this->conn)) {
				throw new Exception(mysql_error($this->conn));
			}
		}# else {
		#	echo "HERE: " . $this->my_error($this->conn) . "\n";
		#}

		return $stmt;
	
	}

	#callback that records that one of the workers failed	
	function handle_failure($func) {
		--$this->workers;
		if($this->verbose) {
			echo "WORKER_FAIL: handle_failure registered unsuccessful worker completion.\n";
		}
		if($this->verbose) {
			print_r($func);
			exit;
		}
		if(!$this->errors) $this->errors=array();
		$this->errors[]=$func;
		
	}

	#this only works with fetch_resultset
	function generic_rowset($func, $handle, $result) {
		--$this->workers;
		if($result['err'] != NULL) {
			throw new Exception('ERROR: ' . $result['err'] . "\n");
		}
		foreach($result->rows as $row) {
			print_r($result);
		}
	}
	
	function collect_rowset($func, $handle, $result) {
		--$this->workers;
		if(is_array($result) && !empty($result['error'])) {
			$this->errors[] = $result['error'];
		}

		if ($this->fetch_method == 'fetch_resultset')  {
			if($result['err'] != NULL) {
				throw new Exception('ERROR: ' . $result['err'] . "\n");
			}
			$sql = "";
		
			if(!$this->conn) throw new Exception("could not connect to coord shard:" . $this->my_error() . "\n");
			#if(!$this->my_select_db($this->tmp_shard['db'])) throw new Exception("could not select db on coord shard:" . $this->my_error($conn) . "\n");
		
			$result =(object)$result;
		
			if( count($result->rows) > 0 ) {
				$sql = "CREATE TEMPORARY TABLE IF NOT EXISTS `{$this->table_name}` (";
				$col_sql = "";
				$this->cols = array_keys($result->rows[0]);
				for($i = 0; $i<count($this->cols);++$i) {
					
					if($this->cols[$i] == "0") continue;
					if($col_sql) $col_sql .= ", ";
					$col_sql .= "`{$this->cols[$i]}` BINARY(255)";
				}
				$sql .= $col_sql . ") ENGINE=MYISAM";
			}
			if($sql) {
				if($this->verbose && !$this->shown_temp_table_create) {
					echo "-- TEMPORARY_TABLE: $sql\n";
					$this->shown_temp_table_create = true;
				}
				if(!$this->my_query($sql, $this->conn)) throw new Exception("Create temporary table:" . $this->my_error($conn));
				foreach($result->rows as $row) {
				
					$vals_sql = "";
					for($i = 0; $i<count($this->cols);++$i) {
						if($this->cols[$i] == "0") continue;
						if($vals_sql) $vals_sql .= ', ';
						$vals_sql .= "'{$row[$this->cols[$i]]}'";
					}
					++$this->row_count;
					$sql = "INSERT INTO `{$this->table_name}` VALUES ({$vals_sql})";
					if(!$this->my_query($sql, $this->conn)) throw new Exception($this->my_error($conn) . "\n");
				}
			}
		}
	}
	
	public function delimited_loader($path,$table,$loadspec,$columns, $use_fifo=true, $start=0, $end=0,$line_terminator="\r\n",$shared=null) {
		if(!trim($path)) return false; 

		#this is the shard where non-partitioned records go.
		#this is probably the directory
		if(!$shared && !empty($this->shared)) $shared = $this->shared;

		if($this->verbose) {
			echo "== delimited loader invoked ==\n";
			echo "== file: $path\n";
			echo "== table: $table ==\n";
			echo "== partition column: " . ($this->partition_column ? $this->partition_column : 'NULL') . "\n";
			echo "== loader spec  ==\n";
			echo print_r($loadspec, true);
			echo "== columns ==\n";
			echo print_r($columns,true);
	
			if($shared !== false) {
				echo "== shared rows==\n" . print_r($shared,true) ;
			}

			#echo "\n== Use Fifo?: " . ($use_fifo ? 'Yes' : 'No') . "\n";
		}

		if(!is_array($columns)) {
			echo "List of columns is expected to be an array!\n";
			return false;
		}

		$ignore = false;
		$errors=false;

		if(!empty($loadspec['ignore'])) $ignore=$loadspec['ignore'];

		if($path != '-') {
			$fh = fopen($path,'r') or die('could not open input stream\n');
		} else {
			$fh = fopen('php://stdin', 'r') or die('could not open input stream\n');
		}
		
		if(!$fh) die('Could not open ' . $path . " for reading\n");
		$shard_col_pos = false;


		if($shared != null && $shared !== false) {
			$conn_dir = $this->my_connect($shared);

			if(!$conn_dir) {
				return array('errors' => array('Could not connect to shared: ' => $this->my_error()));
			}

		}



		if($this->partition_column ) {
			foreach($columns as $key => $column) {
				if($column == $this->partition_column) {
					$shard_col_pos = $key;
					break;
				}
			}
		}

		if($shard_col_pos === false && $this->partition_column) {
			if($this->verbose) {
				echo("== Did not find column: '{$this->partition_column}' in list of columns: ");
				if(!$shared) echo("rows will be sent to all shards\n"); else echo("rows will be sent to the shared database\n");
			}
		}
	
		if(!$this->partition_column) {
			if($this->verbose) {
		
			echo("== Partition column is not set: ");
			if(!$shared) echo("rows will be sent to all shards\n"); else echo("rows will be sent to the shared database.\n");
			}
		}

		$loadspec['delimiter'] = str_replace(array('\\','|','/'),array("\\","\\|", "\\/"), $loadspec['delimiter']);

		if(!empty($loadspec['enclosure'])) {
			$regex = "/{$loadspec['enclosure']}([^{$loadspec['enclosure']}]*){$loadspec['enclosure']}|{$loadspec['delimiter']}/ix";
		} else {
			$regex = "/{$loadspec['delimiter']}/";
		}

		if($this->verbose) {
			echo "== regex from loadspec: $regex\n";
		}

		#read each line from the file, then split it up and look up the shard based on the value
		#the mapper is expected to create values if they do not already exist in the mapping

		$loader_handles = array();
		$loader_fifos = array();

		$errors = false;
		$line_num = 0;	
		$columns_str = join(",", $columns);
		$skipped=false; #have we skipped the number of lines we were asked to skip?


		while(!feof($fh)) {
			if($end >0) {
				$lines = ShardQuery::read_chunk($fh, $start, $end, $line_terminator,10000,$this->chunk) ;
				#we only skip forward once
				$start=false;
			} else {
				$skipped=true; #skipping only happens in the first chunk
				$lines = fgets($fh);
				$lines = array($lines);
			}

			foreach($lines as $line) {
				if(!trim($line)) continue;
				#echo "[{$this->chunk}]: $line\n";
				++$line_num;
				if(!$ignore && $errors !== false) {
					print_r($errors);
				#	break;
				}
				if(!$skipped && !empty($loadspec['skip']) && $loadspec['skip'] >= $line_num)  continue;
				
				$values = array();

				$this->force_shard=false;
				$info=false;
				if($shard_col_pos !== false) {
					
                			$values = preg_split($regex, $line,-1,PREG_SPLIT_NO_EMPTY| PREG_SPLIT_DELIM_CAPTURE );
					$info=$this->get_partition_info($this->partition_column, $values[$shard_col_pos]);

					if(empty($values[$shard_col_pos])) continue;
					$info=$this->get_partition_info($this->partition_column, $values[$shard_col_pos]);
					
					if(!$info) {
						$err = "Discarded row because the partition mapper did not return a valid result for the given partition column.\n";
						$errors[] = array($err=> $line);
						continue;
					} 
					if(is_array($info)) {
						$keys=array_keys($info);
						$this->force_shard=$keys[0];
					} else {
						$this->force_shard=$info;
					}
				}
				
				#we need a list of values for do_insert, which we only use if use_fifo is false
				if(!$use_fifo && empty($values)) {
                			$values = preg_split($regex, trim($line),-1,PREG_SPLIT_NO_EMPTY| PREG_SPLIT_DELIM_CAPTURE );
				}

				if($shared !== false && $this->force_shard === false) {
					if($use_fifo) {
						if(empty($loader_fifos['_shared_']))	{
							if(!empty($loadspec['method'])) {
								switch(strtolower($loadspec['method'])) {
						
								case 'split':
									$fifo = $this->start_split($table,$loadspec,$shared);
		
									break;	
								case 'load':
								case 'default':
									$fifo = $this->start_fifo($table,$loadspec,$shared,$columns_str);
								}

							} else {
								$fifo = $this->start_fifo($table,$loadspec,$shared,$columns_str);
							}
							if($fifo == false) {
								return(array(array("Could not open FIFO for table:{$table}" => "for shard:" . print_r($shard, true))));
							}
							$loader_fifos['_shared_'] = $fifo;
						} 

						$result = fwrite($loader_fifos['_shared_']['fh'], $line) or die('could not write data');
					} else {
						if(empty($loader_handles['_shared_'])) {
							$loader_handles['_shared_'] = $this->my_connect($shared);
							$this->my_query('begin',$loader_handles['_shared_']);
						}
                				$values = preg_split($regex, trim($line),-1,PREG_SPLIT_NO_EMPTY| PREG_SPLIT_DELIM_CAPTURE );
						$result = $this->do_insert($values,$loadspec,$table,$columns,$columns_str,$loader_handles,'_shared_'); 
						if(is_array($result)) {	
							print_r($result);
						} else {
							if($result === false) {
								die('Could not insert!\n');
							}
						}
					}

				} else {

					foreach($this->shards as $shard_key => $shard) {
						#only send the row the correct shard
						if($this->force_shard && ($this->force_shard != $shard_key)) {
							continue;
						} 
						if($use_fifo) {
							if(empty($loader_fifos[$shard_key]))	{
								if(!empty($loadspec['method'])) {
									switch(strtolower($loadspec['method'])) {
										case 'split':
											$fifo = $this->start_split($table,$loadspec,$shard);
											break;
		
										default:
											$fifo = $this->start_fifo($table,$loadspec,$shard,$columns_str);
									}

								} else {
									$fifo = $this->start_fifo($table,$loadspec,$shard,$columns_str);
								}
								if($fifo == false) {
									return(array(array("Could not open FIFO for table:{$table}" => "for shard:" . print_r($shard, true))));
								} 
								$loader_fifos[$shard_key] = $fifo;
							}
							#FIXME: check how much data was written
							$result = fwrite($loader_fifos[$shard_key]['fh'], $line);
						} else 	{
							if(empty($loader_handles[$shard_key]))	{
								$loader_handles[$shard_key] = $this->my_connect($shard);
								$this->my_query('begin',$loader_handles[$shard_key]);
							}
							$result = $this->do_insert($values,$loadspec,$table,$columns,$columns_str,$loader_handles,$shard_key); 
							if(is_array($result)) {	
								print_r($result);
								// exit;
							} else {
								if($result === false) {
									die('Could not insert!\n');
								}
							}
						}
					

						if(empty($loader_handles[$shard_key]) && empty($loader_fifos[$shard_key])) {
							$err= "Discarded row because the destination database could not be connected.\n";
							$errors[] =array($err=> $line);
							continue;

						}
					}
				}
			}
			if($end && ftell($fh) >= $end)  break;
		}				

		foreach($loader_handles as $handle) {
			$this->my_query('commit',$handle);
			$this->my_close($handle);
		}
		
		#this will automatically end the process reading from the other end of the named pipe

		foreach($loader_fifos as $handle) {
			#fputs($handle['fh'], 0x04);
			#if(feof($fh)) fputs($handle['fh'],"\n");
			fflush($handle['fh']);
			fclose($handle['fh']);
		}


		$this->errors = $errors;

		return($this->errors);
		
	}


	#used by the loader to insert a row
	protected function do_insert(&$values,&$loadspec,&$table,$columns,&$columns_str,&$loader_handles,$shard_key) {
		$vals = "";
	
		foreach($columns as $colkey => $column ) {
			if(empty($values[$colkey])) $values[$colkey] = '';
		}
	
		$vals = "'" . join("','", $values) . "'";
		if(empty($loadspec['ignore'])) $loadspec['ignore'] = ''; else $loadspec['ignore']='IGNORE';
		$sql = "INSERT {$loadspec['ignore']} INTO $table ($columns_str) VALUES ($vals)"; 
	
		if(!$this->my_query($sql, $loader_handles[$shard_key])) {
			$err = "WARNING: Discarded row because the insertion into destination database failed:$sql\n";
			return array($err=>mysql_error($loader_handles[$shard_key]));
		}

		return true;
	}

	protected function start_split($table,$loadspec,$shard) {

		$fname =  str_replace('.','',microtime(true)) . '.txt';

		if(empty($loadspec['output_directory'])) $loadspec['output_directory'] = './';
		$path = $loadspec['output_directory'];
		if(substr($path,-1) != '/') {
			$path .= "/";
		}
		$path .= $shard['host'] . '/' . $shard['db'] . '/' . $table . '/';
		if(!file_exists($path)) mkdir($path,0777,true) or die("could not create $path\n");
		$path .= $fname;

		$fh = fopen($path, 'wb') or die('Could not open ' . $path . " for writing\n");	

		return(array('fh' => $fh, 'ph' => null)); 

	}


	protected function start_fifo($table,$loadspec,$shard, $columns_str) {

		$rand = mt_rand(1,100000000);
		$path = '/tmp/sq_tmp_fifo#' . $rand;
		$result = posix_mkfifo($path,'0700');
		if(!$result) {
			return array("Could not open FIFO" => $path);
		}

		if(empty($loadspec['ignore'])) $loadspec['ignore'] = ' '; else $loadspec['ignore'] = ' IGNORE ';
		$sql = "";

		$load="LOAD DATA LOCAL INFILE \"$path\" IGNORE INTO TABLE `{$shard['db']}`.`$table`";
		if(!empty($loadspec['delimiter'])) {
			$sql = " FIELDS TERMINATED BY \"" . $loadspec['delimiter'] . "\" ";
		}

		if(!empty($loadspec['enclosure'])) {
			if($sql == "") {
				 $sql = ' FIELDS ';
			}
			
			$sql .= "OPTIONALLY ENCLOSED BY \"" . $loadspec['enclosure'] . "\" ";
			
		}
		if(empty($loadspec['line_terminator'])) $loadspec['line_terminator'] = "\\n";
		$line_terminator = str_replace(array("\r","\n"),array("\\r","\\n"), $loadspec['line_terminator']);

		$sql .= "LINES TERMINATED BY \"" . $line_terminator . "\" ";
		#$sql .= "LINES TERMINATED BY \"\\n\" ";

		if(!empty($loadspec['skip'])) {
			$sql .= "SKIP {$loadspec['skip']} LINES ";
		}

		$sql .= "($columns_str)";

		$load .= $sql;

		$cmdline = "mysql -u{$shard['user']} -h{$shard['host']} -P{$shard['port']} {$shard['db']}";
		if(!empty($shard['password']) && trim($shard['password'])) {
			$cmdline .= " -p{$shard['password']}";
		}
		$cmdline .= " -e 'set @bh_dataformat=\"variable\";{$load}'";
		
		$pipes=null;	
		echo $cmdline . "\n";
		$ph = proc_open($cmdline, array(), $pipes);

		if(!$ph) {
			return array("Could not open FIFO for table {$table}, for shard"=>print_r($shard,true));
		}

		#FIXME
		#we need to check to see if there was an error running the mysql client

		#this won't block because we've started a reader in a coprocess 
		$fh = fopen($path, 'w');	

		return(array('fh' => $fh, 'ph' => array('ph'=>$ph, 'pipes'=>$pipes)));

	}


	protected static function read_chunk($fh, $start, $end, $line_terminator = "\r\n",$batch_size=10000,$chunk=0) {
		$lines = array();
		$fgets_safe=false; #this is only safe for terminators like \r\n or \n.  fgets() can be called, which is 8x faster
		
		#When we start reading at offset zero, then we must not skip
		#to the first line terminator and instead must read all chars.
		#set $started = true to make sure this happens.
		if($start == 0) {
			$started = true;	
		} else {
			$started = false;
		}
	
		#seek to the requested starting position (which might be zero)
		if($start >0) {
			fseek($fh, $start, SEEK_SET);
		}
	
		#this var holds the current line that is being built.  It only
		#can be considered an entire line once a line terminator has
		#been located or we hit eof
		$line = "";
	
		#stop reading when we have reached the end of file
		$terminator_len = strlen($line_terminator);

		if(substr($line_terminator,-1,1) == "\n") {
			$fgets_safe=1;
		}

		while(!feof($fh)) {

			/* Most of the time the delimiter will be only one char (newline)
			but sometimes it might more more than one.  Always read at least
			the number of bytes in the delimiter */
			if($fgets_safe==1) {
				$chars = fgets($fh);
			} else {
				$chars = fread($fh, $terminator_len);
			}

			$last_chars = substr($chars,-1, $terminator_len);

			/* Skip all input until we encountered a line terminator,
	                then start reading.  The only time that $started will
			be true at the start of the loop is when the requested
			starting offset is zero (see above).
			*/
	
			if(!$started) {
				if($last_chars == $line_terminator) {
					$started = true;
				}
				continue;
			}
	
		
			#If chars contains the end-of-the-line
			if($last_chars == $line_terminator) {
				$lines[]=$line . $chars;
				$line = "";

				if( $batch_size !== false && count($lines) >= $batch_size) {
					break;
				}

				if(ftell($fh) >= $end) {
					break; 
				}	
				
			} else {
				$line .= $chars;
			}

		}
	
		if(feof($fh) && trim($line)) {
			$lines[]=$line; 
			$line = "";
		}

		return $lines;	
	}
}
