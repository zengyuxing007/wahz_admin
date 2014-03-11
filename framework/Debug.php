<?php
echo '
<!--
***************************** debug *********************************************
-->
<hr>
<style>
.tclass, .tclass2 {
text-align:left;width:760px;border:0;border-collapse:collapse;margin-bottom:5px;table-layout: fixed; word-wrap: break-word;background:#FFF;}
.tclass table, .tclass2 table {width:100%;border:0;table-layout: fixed; word-wrap: break-word;}
.tclass table td, .tclass2 table td {border-bottom:0;border-right:0;border-color: #ADADAD;}
.tclass th, .tclass2 th {border:1px solid #000;background:#CCC;padding: 2px;font-family: Courier New, Arial;font-size: 11px;}
.tclass td, .tclass2 td {border:1px solid #000;background:#FFFCCC;padding: 2px;font-family: Courier New, Arial;font-size: 11px;}
.tclass2 th {background:#D5EAEA;}
.tclass2 td {background:#FFFFFF;}
.firsttr td {border-top:0;}
.firsttd {border-left:none !important;}
.bold {font-weight:bold;}
</style>
';
	$class = 'tclass2';
	if(isset($GLOBALS['sql']) && $values = $GLOBALS['sql']) {
		
		foreach ($values as $dkey => $debug) {
			($class == 'tclass')?$class = 'tclass2':$class = 'tclass';
			echo '<table cellspacing="0" class="'.$class.'"><tr><th rowspan="2" width="20">'.($dkey+1).'</th><td width="60"> '.$debug['time'].' ms</td><td width="100"> '.$debug['database'].'</td><td class="bold">'.htmlspecialchars($debug['sql']);if(!empty($debug['sql_info']))echo $debug['sql_info'];echo "\n<br />";if(!empty($debug['sql_real']))echo $debug['sql_real'];echo'</td></tr>';
			if(!empty($debug['explain'])) {
				echo '<tr><td>Explain</td><td><table cellspacing="0"><tr class="firsttr"><td width="5%" class="firsttd">id</td><td width="10%">select_type</td><td width="12%">table</td><td width="5%">type</td><td width="20%">possible_keys</td><td width="10%">key</td><td width="8%">key_len</td><td width="5%">ref</td><td width="5%">rows</td><td width="20%">Extra</td></tr><tr>';
				foreach ($debug['explain'] as $ekey => $explain) {
					($ekey == 'id')?$tdclass = ' class="firsttd"':$tdclass='';
					if(empty($explain)) $explain = '-';
					echo '<td'.$tdclass.'>'.$explain.'</td>';
				}
				echo '</tr></table></td></tr>';
			}
			echo '</table>';
		}
	}

	if($files = get_included_files()) {
		($class == 'tclass')?$class = 'tclass2':$class = 'tclass';
		echo '<table class="'.$class.'">';
			foreach ($files as $fkey => $file) {
				echo '<tr><th width="20">'.($fkey+1).'</th><td>'.$file.'</td></tr>';
			}
		echo '</table>';
	}

	if($cookies = $_COOKIE) {
		($class == 'tclass')?$class = 'tclass2':$class = 'tclass';
		$i = 1;
		echo '<table class="'.$class.'">';
			foreach ($cookies as $ckey => $value) {
				echo '<tr><th width="20">'.$i.'</th><td width="250">$_COOKIE[\''.$ckey.'\']</td><td>'.var_export($value, true).'</td></tr>';
				$i++;
			}
		echo '</table>';
	}
	if(0 && $server = $_SERVER) {
		($class == 'tclass')?$class = 'tclass2':$class = 'tclass';
		$i = 1;
		echo '<table class="'.$class.'">';
			foreach ($server as $ckey => $value) {
				echo '<tr><th width="20">'.$i.'</th><td width="250">$_SERVER[\''.$ckey.'\']</td><td>'.var_export($value, true).'</td></tr>';
				$i++;
			}
		echo '</table>';
	}
	if($values = $_GET) {
		($class == 'tclass')?$class = 'tclass2':$class = 'tclass';
		$i = 1;
		echo '<table class="'.$class.'">';
			foreach ($values as $ckey => $value) {
				echo '<tr><th width="20">'.$i.'</th><td width="250">$_GET[\''.$ckey.'\']</td><td>'.var_export($value, true).'</td></tr>';
				$i++;
			}
		echo '</table>';
	}
	// wangdeguo,add echo $_POST
	if($values = $_POST) {
		($class == 'tclass')?$class = 'tclass2':$class = 'tclass';
		$i = 1;
		echo '<table class="'.$class.'">';
			foreach ($values as $ckey => $value) {
				echo '<tr><th width="20">'.$i.'</th><td width="250">$_POST[\''.$ckey.'\']</td><td>'.htmlentities($value).'</td></tr>';
				$i++;
			}
		echo '</table>';
	}
	
	if($values = $_FILES) {
		($class == 'tclass')?$class = 'tclass2':$class = 'tclass';
		$i = 1;
		echo '<table class="'.$class.'">';
			foreach ($values as $ckey => $value) {
				echo '<tr><th width="20">'.$i.'</th><td width="250">$_FILES[\''.$ckey.'\']</td><td>'.var_export($value, true).'</td></tr>';
				$i++;
			}
		echo '</table>';
	}

	// wangdeguo,add echo $_SESSION
	// var_dump($_SESSION);
	if($values = $_SESSION) {
		($class == 'tclass')?$class = 'tclass2':$class = 'tclass';
		$i = 1;
		echo '<table class="'.$class.'">';
			foreach ($values as $ckey => $value) {
				echo '<tr><th width="20">'.$i.'</th><td width="250">$_SESSION[\''.$ckey.'\']</td><td>'.var_export($value, true).'</td></tr>';
				$i++;
			}
		echo '</table>';
	}

	echo '<table class="tclass"><tr><td>time: ';
	echo time() - $_SERVER['REQUEST_TIME'];
	echo '</td></tr></table>';
echo '<pre>';


echo "\n memory_get_usage：";
echo memory_get_usage();

echo "\n objects：";
//var_dump(get_declared_classes());
/*
va
r_dump($M_EM);*/
?>