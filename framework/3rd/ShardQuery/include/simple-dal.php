<?php
interface SimpleDAL {
	function my_connect($server=null);
	function my_query($sql='', $conn=null);
	function my_fetch_assoc($stmt=null);
	function my_fetch_array($stmt=null);
	function my_errno($stmt = null);
	function my_error($stmt = null); 
	function my_select_db($db, $conn);
	function my_free_result($stmt=null);
	function my_close($conn = null);
	function my_commit($conn = null);
	function my_rollback($conn = null);
	function my_real_escape_string($string, $conn = null);
}
