;(function(){
    var res = GetRequest();
    var par = res['index'];
    if(par!='gfan'){
        var ua=navigator.userAgent.toLowerCase();
        var contains=function (a, b){
            if(a.indexOf(b)!=-1){return true;}
        };
		//将下面的http://m.yijile.com改成你的wap手机版页面地址 如我的 http://m.yijile.com 
        var toMobileVertion = function(){
			HiddenHeader();
            //window.location.href = 'http://m.yijile.com/'
        }
		var toPcVertion = function(){
			ShowHeader();
            //window.location.href = 'http://m.yijile.com/'
        }
        
        if(contains(ua,"ipad")||(contains(ua,"rv:1.2.3.4"))||(contains(ua,"0.0.0.0"))||(contains(ua,"8.0.552.237"))){return false}
        if((contains(ua,"android") && contains(ua,"mobile"))||(contains(ua,"android") && contains(ua,"mozilla")) ||(contains(ua,"android") && contains(ua,"opera"))
    ||contains(ua,"ucweb7")||contains(ua,"iphone")){toMobileVertion();}else{toPcVertion();}
    }
})();
function GetRequest() {
   var url = location.search; //获取url中"?"符后的字串
   var theRequest = new Object();
   if (url.indexOf("?") != -1) {
      var str = url.substr(1);
      strs = str.split("&");
      for(var i = 0; i < strs.length; i ++) {
         theRequest[strs[i].split("=")[0]]=unescape(strs[i].split("=")[1]);
      }
   }
   return theRequest;
}

function HiddenHeader() {
	var header = document.getElementById("myHeader");
	header.style.display="none";
	$('#botron').css({
		"margin-top":"0px"
	});
	
	$('.col-xs-4').find('img').css({
		"width":"100%",
		"height":"44px"
	});
	
}
function ShowHeader() {
	var header = document.getElementById("myHeader");
	header.style.display="block";
}



