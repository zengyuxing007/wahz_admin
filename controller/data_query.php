<?php

class Action
{
	protected $res_type = 11;

	public function __call($param = '', $param=array())
	{
	}

	/**
	 * Sql查询
	 *
	 */
	public function myquery()
	{
		$tpl = array(
			'title' => 'Sql查询',
			'desc'  => '',
			'helper' => false //是否显示帮助信息
		);
		Response::assign('tpl', $tpl);

		$sql = Request::Get('sql', '');
		if(empty($sql))
		{
		    Response::display('server/data_query.html');
		    return;
		}
		if(false !== strpos($sql,"limit") || false !== strpos($sql,"LIMIT"))
		{
		}
		else
		{
		    $sql .= " limit 100";
		}
		$data_rs = _shardquery()->query($sql);
		$info = array();
		$count = 0;
		if(is_resource($data_rs))
		{
		    while($info[] = mysql_fetch_assoc($data_rs))
		    {
			++$count;
		    }
		    mysql_free_result($data_rs);
		}
		Response::assign('youInputSql',$sql);
		Response::assign('count',$count);
		if (empty($info)){
		    Response::assign('msg',"error or no data!");
		}
		else
		{
		    $column = array_keys($info[0]);
		    Response::assign('info',$info);
		    Response::assign('column',$column);
		}
		Response::display('server/data_query.html');
	}
}

?>
