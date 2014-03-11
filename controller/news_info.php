<?php

class Action
{

    public function __call($param = '', $param=array())
    {
    }

    public function news_list($id='#')
    {
			$tpl = array(
							'title' => ($id == '#') ? '新闻查询' :'新闻查询',
							'desc'  => '',
							'helper' => true, //是否显示帮助信息
					//		'search' => $id == '#' ? true :false
						);
			Response::assign('tpl', $tpl);

			$pager = new Pager(10);
			$news_list = _model('news_simple', 'wahz')->getAll('*',array(), 'ORDER by create_time DESC '.$pager->getLimit());
			$count =  _model('news_simple', 'wahz')->getTotal();

			if ($pager->generate($count)) {
					Response::assign('pager', $pager);
			}
			Response::assign('count', $count);
			Response::assign('pagearray', $pager->getpagesArray(9));
			Response::assign('news_list', $news_list);	

			Response::display('news_list.html');
    }

    //新闻详情 
    function news_detail($news_id)
	{
			$tpl = array(
							'title'  => '新闻详情',
							'search' => true,
						);
			Response::assign('tpl',$tpl);

			$news= _model('news_simple','wahz')->read(array('id'=>$news_id));
			Response::assign('news',$news);

			Response::display('news_detail.html');

	}

	function del_all(){

			$id = $_GET['id'];
			$id = trim($id, ',');

			if (is_int($id)) {
					$result = _model('news_simple', 'wahz')->delete(array('id'=>$id));
			} elseif(is_string($id)) {
					$newid = explode(',', $id);
					foreach ($newid as $value) {
							$result = _model('news_simple', 'wahz')->delete(array('id'=>$value));
					}
			}
			if($result) {
					$res = array('info'=>'ok');
			} else {
					$res = array('info'=>'false');
			}
			header('Content-type: application/json');
			echo json_encode($res);
	}
}


?>
