$(function(){
	//添加一个用于去除mozilla中radio和checkbox的bug问题
	if($.browser.mozilla) $("form").attr("autocomplete", "off");
	$(".selectAll").click(function (){
		var select = $(this).attr("checked");
		if(select){
			 $(".select_s input").attr("checked",true);
			 $(".selectAll").attr("checked",true);
		}else{
			$(".selectAll").attr("checked",false);
		    $(".select_s input").attr("checked",false);
		}
	});
	if (typeof(predefineNotice) != 'undefined') {
		$.extend(prompt,predefineNotice);
	}
	var predefineParam = predefineParam;
	$("#deleteAll").click(function(){
		del(url);
	});
	$(".deleteOne").click(function(){
		var id = $(this).parents("tr").attr("id");
		var text = id.substring(5);
		del_one(url,text);
	});
	
	// 编辑器采用上传模式 *require res_type
	var uploadUrl = "/admin/ajax_upload&source=xheditor&immediate=1&file_field=filedata";
	//$('.xheditor-upload').xheditor(true,{height:300,upImgUrl:uploadUrl,upImgExt:"jpg,jpeg,gif,png",upFlashUrl:uploadUrl,upFlashExt:"swf",upMediaUrl:uploadUrl,upMediaExt:"flv"});
});

//帮助菜单切换
$(function(){
	$("#content .pageTit .opt").click(function(){
		$("#content .helper").toggle('normal')
	})
})

var prompt = {
		prompt:"确定吗?",
		nochange:"您没有选择要操作的记录",
		errors:"操作失败"
			};
function delete_one(url){
	if(!confirm(prompt.prompt)) return false;
	window.location.href = url;
}
function del_one(url,id){
	if(!confirm(prompt.prompt)) return false;
	var sendata = {id:id};
	if (typeof(predefineParam) != 'undefined') {
		$.extend(sendata, predefineParam);
	}
	$.getJSON(url, sendata,function(data){
		if(data.info == 'ok'){
			
			var text = 'list_'+id;
			if (typeof(predefineFun) == 'undefined') {
				$("#"+text).remove();
			} else {
				$.extend({ybo:predefineFun});
				$.ybo(text);
			}
		}else{
			alert(prompt.errors);
			return false;
		}
	});
}
function del(url){
	if(!confirm(prompt.prompt)) return false;
	var ids = '';
	$(".select_s").each(function(e){
		if($(".select_s input").eq(e).attr("checked")==true) {	
			var text, id;
			text = $(this).attr("id");
			id = text.substring(5);
			ids += id+',' ;
		}
	});
	if(!ids){
		alert(prompt.nochange);
		return false;
	}
	var sendata = {id:ids};
	if (typeof(predefineParam) != 'undefined') {
		$.extend(sendata, predefineParam);
	}
	$.getJSON(url, sendata, function(json){
		if (json.info == 'ok'){
				var $obj = $(".select_s input:checked").parents("tr");
				if (typeof(predefineFun) == 'undefined') {
					$obj.remove();
				} else {
					$obj.each(function(i, n){
						var jobj = $(n);
						$.extend({ybo:predefineFun});
						$.ybo(jobj.attr("id"));
					});
				}
			$(".selectAll").attr("checked",false);
		} else {
			alert(prompt.errors);
		}
	});
}
/*
function getQueryStringRegExp(name)
{
    var reg = new RegExp("(^|\\?|&)"+ name +"=([^&]*)( \\s|&|$)", "i");
    if (reg.test(top.window.location.href)) return unescape(RegExp.$2.replace(/\+/g, " ")); return "";
}
*/
function getQueryStringRegExp(name, value)
{
    var reg = new RegExp("(^|\\?|&)"+ name +"=([^&]*)( \\s|&|$)", "i");
    if (reg.test(top.window.location.href)) {
        return top.window.location.href.replace(reg, "$1"+name+"="+value+"$3");
    } else {
        return false;
    }
}
function JsRedirect(url){
	if((url.substr(0,1) == '/') || (url.substr(0,4) == 'http')){
		window.location.href = url;
	} else {
		window.location.href = '/' + url;
	}
	
}
//多种展现方式。 判断url 是否有 charts_type
function charts_type(type){
	var newUrl = '';
	var _chart_type = getQueryStringRegExp('charts_type', type);
	if(_chart_type == false){
		newUrl = _thisUrl + '&charts_type=' + type;
	}else{
		newUrl = _chart_type;
	}
	JsRedirect(newUrl);
}

function load_api_charts(id,url){
	$.get(url,function(data){
		if (data == 'error'){
			$("#"+id).html('没有数据!')
		}else {	
			$("#"+id).html(data)
		}
	})
}
