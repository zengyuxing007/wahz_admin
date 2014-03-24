<?php

require_once ROOT_PATH . '/lib/resizeimg.php';

class Action
{
	public function __construct()
	{

		//上传文件类型列表
		$this->uptypes=array(
				'image/jpg',
				'image/jpeg',
				'image/png',
                'image/pjpeg',
				'image/x-png',
                'image/bmp',
				'image/gif',
				);
		$this->tpl = array(
				'title' => '添加新闻',
				'desc'  => '',
				'helper' => true//是否显示帮助信息
				);
	}
	public function __call($param = '', $param=array())
	{
	}


	public function view()
	{


		Response::assign('tpl',$this->tpl);
		Response::assign('no_upload',1);
		Response::assign('uptypes',implode(',',$this->uptypes));
		Response::display('server/add_news.html');
		return;
	}

	public function upload()
	{

		$max_file_size=2000000;     //上传文件大小限制, 单位BYTE
		$destination_folder="uploadimg/"; //上传文件路径
                $thumbnail_folder="thumbnail/"; //缩略图路径
		$watermark=0;      //是否附加水印(1为加水印,其他为不加水印);
		$watertype=1;      //水印类型(1为文字,2为图片)
		$waterposition=1;     //水印位置(1为左下角,2为右下角,3为左上角,4为右上角,5为居中);
		$waterstring="http://wahz.com/";  //水印字符串
		$waterimg="xplore.gif";    //水印图片
		$imgpreview=1;      //是否生成预览图(1为生成,其他为不生成);
		$imgpreviewsize=1/2;    //缩略图比例

		//var_dump($_FILES["upfile"]);
		// return;
		$file = $_FILES["upfile"];

		if (!is_uploaded_file($file["tmp_name"]))
			//是否存在文件
		{
			Response::assign('tpl',$this->tpl);
			Response::assign('no_upload',1);
			Response::assign('error',"图片不存在!");
			Response::display('server/add_news.html');
			return;
		}

		if($max_file_size < $file["size"])
			//检查文件大小
		{
			$error = "文件太大!";
			Response::assign('tpl',$this->tpl);
			Response::assign('no_upload',1);
			Response::assign('error',$error);
			Response::display('server/add_news.html');
			return;
		}

		if(!in_array($file["type"], $this->uptypes))
			//检查文件类型
		{
			$error = "文件类型不符!".$file["type"];
			Response::assign('tpl',$this->tpl);
			Response::assign('no_upload',1);
			Response::assign('error',$error);
			Response::display('server/add_news.html');
			return;
		}

		if(!file_exists($destination_folder))
		{
			mkdir($destination_folder);
		}

		$filename=$file["tmp_name"];
		$image_size = getimagesize($filename);
		$pinfo=pathinfo($file["name"]);
		$ftype=$pinfo['extension'];
		$destination = $destination_folder.time().".".$ftype;
		if (file_exists($destination) && $overwrite != true)
		{
			$error = "同名文件已经存在了";

			Response::assign('tpl',$this->tpl);
			Response::assign('no_upload',1);
			Response::assign('error',$error);
			Response::display('server/add_news.html');
			return;
		}

		if(!move_uploaded_file ($filename, $destination))
		{
			$error = "移动文件出错";
			Response::assign('tpl',$this->tpl);
			Response::assign('no_upload',1);
			Response::assign('error',$error);
			Response::display('server/add_news.html');
			return;
		}

		$pinfo=pathinfo($destination);
		$fname=$pinfo["basename"];
		$html = " <font color=red>已经成功上传</font><br>文件名:  <font color=blue>".$destination_folder.$fname."</font><br>";
		$html .= " 宽度:".$image_size[0];
		$html .= " 长度:".$image_size[1];
		$html .= "<br> 大小:".$file["size"]." bytes";

		if($watermark==1)
		{
			$iinfo=getimagesize($destination,$iinfo);
			$nimage=imagecreatetruecolor($image_size[0],$image_size[1]);
			$white=imagecolorallocate($nimage,255,255,255);
			$black=imagecolorallocate($nimage,0,0,0);
			$red=imagecolorallocate($nimage,255,0,0);
			imagefill($nimage,0,0,$white);
			switch ($iinfo[2])
			{
				case 1:
					$simage =imagecreatefromgif($destination);
					break;
				case 2:
					$simage =imagecreatefromjpeg($destination);
					break;
				case 3:
					$simage =imagecreatefrompng($destination);
					break;
				case 6:
					$simage =imagecreatefromwbmp($destination);
					break;
				default:
					die("不支持的文件类型");
					exit;
			}

			imagecopy($nimage,$simage,0,0,0,0,$image_size[0],$image_size[1]);
			imagefilledrectangle($nimage,1,$image_size[1]-15,80,$image_size[1],$white);

			switch($watertype)
			{
				case 1:   //加水印字符串
					imagestring($nimage,2,3,$image_size[1]-15,$waterstring,$black);
					break;
				case 2:   //加水印图片
					$simage1 =imagecreatefromgif("xplore.gif");
					imagecopy($nimage,$simage1,0,0,0,0,85,15);
					imagedestroy($simage1);
					break;
			}

			switch ($iinfo[2])
			{
				case 1:
					//imagegif($nimage, $destination);
					imagejpeg($nimage, $destination);
					break;
				case 2:
					imagejpeg($nimage, $destination);
					break;
				case 3:
					imagepng($nimage, $destination);
					break;
				case 6:
					imagewbmp($nimage, $destination);
					//imagejpeg($nimage, $destination);
					break;
			}

			//覆盖原上传文件
			imagedestroy($nimage);
			imagedestroy($simage);
		}

                //生成缩略图

                $resizeimage = new resizeimage($destination_folder.$fname, "120", "120", "1",$thumbnail_folder.$fname);


		if($imgpreview==1)
		{
			$html .= "<br>图片预览:<br>";
			$html .= "<img src=\"".$destination."\" width=".($image_size[0]*$imgpreviewsize)." height=".($image_size[1]*$imgpreviewsize);
			$html .= " alt=\"图片预览:\r文件名:".$destination."\r上传时间:\">";
			$html .= "<img src=\"".$thumbnail_folder.$fname."\" width=120 height=120 >";
		}



		Response::assign('tpl',$this->tpl);
		Response::assign('no_upload',0);
		Response::assign('upload',1);
		Response::assign('image',$html);
		Response::assign('image_url',$fname);
		Response::display('server/add_news.html');
		return;


	}

	public function add()
	{

		$title=Request::Post('title','');
		$imgUrl=Request::Post('img_url','');
		$type=Request::Post('type',1);
		$content=Request::Post('content','');
		if($title){
			//	    echo $title;
			//echo "content:$content";
			//echo 'dsdsdsdsds';
			$data = array(
					'title' => $title,
					'type' => $type,
					'image_url' => $imgUrl,
					'content' => $content,
					'create_time' => time(),
				     );
			try{
				_model('news_simple','wahz')->executesql($data);
				//echo "<br>add successfule!";
				Response::assign('add_result',"添加成功...");
			}catch (Exception $e){
				//echo "<br>add failed:$e";
				Response::assign('add_result',"添加失败...");
			}
		}
		else{

			//echo $title;
			//echo "content:$content";
			//echo "but why.....here";
			Response::assign('add_result',"标题或内容为空...");
		}
		Response::assign('tpl',$this->tpl);
		Response::display('server/add_news.html');
	}

}

?>
