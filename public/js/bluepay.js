
var telcoPrefix = [
/*indosat*/"0814","0815","0816","0855","0856","0857","0858",
/*xl*/,"0817","0818","0819","0831","0832","0838","0859","0877","0878","0879",
/*hutchison*/,"0895","0896","0897","0898","0899"
/*telkomsel*/,"0811","0812","0813","0821","0822","0823","0852","0853"
];

var INDOSAT = "indosat";
var TELKOMSEL = "telkomsel";
var HUTCHISON = "hutchison";
var XL = "xl";
var description = {
	'th':{
		'605':'Can not submit the same transaction,please close this page and start from the store again.',
		'700':'{}  อยู่ระหว่างการบำรุงรักษาโปรดเรียกเก็บเงินผ่านทาง telco อื่น ๆ.',
	},
	'in':{
		'605':'Tidak bisa melakukan transaksi yang sama, silahkan keluar dari halaman ini dan ulangi dari awal lagi',
		'700':'{} sedang dalam perawatan, tolong bayar via telco lainnya.',
	},
	'vn':{
		'605':'Can not submit the same transaction,please close this page and start from the store again.',
		'700':'{} về bảo trì, xin vui lòng tính phí qua những người khác telco.',
	},
	'test':{
		'605':'Can not submit the same transaction,please close this page and start from the store again.',
		'700':'{}  on maintance,please charge via others telco.',
	}
}

var supportTelco = ['ais','true','dtac','viettel','mobifone','vinaphone','telkomsel','xl','indosat','hutchison','smartfren'];


var placeHolder = [
/*indosat*/		"(contoh:0857xxxxx) masukkan nomor hp anda",
/*xl*/      	"(contoh:0818xxxxx) masukkan nomor hp anda",
/*hutchison*/	"(contoh:0898xxxxx) masukkan nomor hp anda",
/*smartfren*/	"(contoh:0881xxxxx) masukkan nomor hp anda",
/*telkomsel*/	"(contoh:0811xxxxx) masukkan nomor hp anda"
];

var telcoPrefixPlaceHolder ={
	'telkomsel':'mendukung:0811,0812,0813,0821,0822,0823,0852,0853',
	'xl':'mendukung:0817,0818,0819,0831,0832,0838,0859,0877,0878,0879',
	'indosat':'mendukung:0814,0815,0816,0855,0856,0857,0858',
	'hutchison':'mendukung:0895,0896,0897,0898,0899',
};
var telcos = {
	'th' : ['ais','true','dtac'],
	'vn' : ['viettel','mobifone','vinaphone'],
	'vn' : ['viettel','mobifone','vinaphone'],
	'in' : ['telkomsel','xl','indosat','hutchison'],
	'test': ['dtac','ais','true']
};
var keyWords = [];
var useHwChannel = [1,690,716,1094];
var sendType = 0;//0 代表，直接生成命令字。1，点击才生成命令字
var message =[];
var closeProductIds = [716,1094,1002,1102];

function isKeywordExist(telco,transactionId){
	window.message = isCachedKeyword(telco,transactionId);
	if(! window.message){
		return 0;
	}
	if (window.message[0] == transactionId) {
		return 1;
	}
	// var kw = window.keyWords[telco];
	// if ( typeof(kw) == "undefined") {
	// 	return 0;
	// }
	// return 1;
}

function isDCB(telco){
	if(telco == XL || telco == HUTCHISON){
		return true;
	}
	return false
}

function getSmsFromCache(){
	return window.message[2];
	// var indexTmp = "";
	// if (typeof(scAndKw) != "undefined") {
	// 	scAndKw.forEach(function(item,index,arr){
	// 		indexTmp = item;
	// 	});
	// }

	// return indexTmp;
}

function getShortCodeFromCache(){
	// var scAndKw = window.keyWords[telco];
	// var indexTmp = "";
	// if (typeof(scAndKw) != "undefined") {
	// 	scAndKw.forEach(function(item,index,arr){
	// 		indexTmp = index;
	// 	});
	// }
	// return indexTmp;
	return window.message[1];
}
function isInSupportTelcos(telco){
	var isIn = -1;
	window.supportTelco.forEach(function(item,index,arr){
		if (telco == item) {
			isIn =index;
		};
	})
	return isIn;
}




function addKeywordWithTelco(telco,shortCode,keyword,transactionId){
	// var scAndKw = [];
	// scAndKw[shortCode] = keyword;
	// //global keyWords;
	// window.keyWords[telco] = scAndKw;
	cacheKeyword(transactionId,telco,shortCode,keyword)

}

function getPlaceHolder(telco){
	if (telco == "indosat") {
		return placeHolder[0];
	}
	if (telco == "xl") {
		return placeHolder[1];
	};
	if (telco == "hutchison") {
		return placeHolder[2];
	}
	if (telco == "smartfren") {
		return placeHolder[3];
	}
	if (telco == "telkomsel") {
		return placeHolder[4];
	};
}

function isInArray(value,array){
	var isExist  = -1;
	array.forEach(function(item,index,arr){
		if (value == item) {
			isExist = 1;
		}		
	});
	return isExist;
}

function checkMsisdnPrefix(hostPre,telco,msisdn){
	
	msisdn = msisdn.substring(0,4);
	return isInArray(msisdn, telcoPrefix) ;
}

function showLoading(){
    $('#modal-alert-loading').modal();
}

function hideLoading(){
    $('#modal-alert-loading').modal('hide');
}

function getBankSc(telco){
	if (telco == "ais") {
		return "4192034";
	}else if(telco == "true"){
		return "4192007";
	}else if (telco == "dtac"){
		return "4078005";
	}
}

function closeChannel(productId,telco){
	//telco == 'telkomsel' || 
	if (closeTelkomsel(productId,telco)) {
		return true;
	};
	if (telco =='hutchison' || telco == TELKOMSEL ||telco == "true") {
		productId = Number(productId);
		
		for (var i = window.closeProductIds.length -1; i >= 0; i--) {
			if(window.closeProductIds[i] == productId){
				return true;
			}
		};
		return false;
	}
}

function closeTelkomsel(productId,telco){

	return false;
}




//根据产品id 隐藏
function showLogo(productId){
	var show = true;
	var array = [812,622,518];
	array.forEach(function(item,index,arr){
		if (Number(productId) == item) {
			show = false;
		}		
	});
	return show;
}

function isNeedMsisdn(telco,isTelkomselHw){
	if(telco == "xl" || telco == "hutchison" || telco == "indosat" || (isTelkomselHw && telco == "telkomsel")){
		return true;
	}else{
		return false;
	}
}



function cache(transactionId,telco){
	var d = new Date();
  	d.setTime(d.getTime()+(1*24*60*60*1000));
  	var expires = "expires="+d.toGMTString();
  	if (sendType == 1 || telco == 'offline' || telco == 'cashcard') {
  		document.cookie = "transactionId="+ transactionId + "; " + expires;
  	}
  	
}

function cacheKeyword(transactionId,telco,shortCode,keyword){
	var d = new Date();
  	d.setTime(d.getTime()+(5*60*60*1000));
  	var expires = "expires="+d.toGMTString();
  	document.cookie = shortCode + "_" + telco + "_"+ transactionId +"=" + keyword + "; " + expires;
  	
}

function isCachedKeyword(telco,transactionId){
	var ca = document.cookie.split(';');
	if(ca[0] == ""){
		return false;
	}
	var message = [];
	 for(var i=0; i<ca.length; i++) {
	    var c = ca[i].trim();
	    c = c.split("=");
	    var temp = c[0].split("_");
	   	if (temp[2] == transactionId && temp[1] == telco) {
			message[0] = transactionId;
			message[1] = temp[0];
			message[2] = c[1];
			message[3] = telco;

	    	return message;
	  	}
	}
	return false;
}

function isCached(transactionId,telco){
	var ca = document.cookie.split(';');
	 for(var i=0; i<ca.length; i++) {
	    var c = ca[i].trim();
	    if (sendType == 1 || telco == 'offline' || telco == 'cashcard') {
	    
	   		if (c == ("transactionId="+ transactionId)) {
	//    	document.cookie = "transactionId=; expires=Thu, 01 Jan 1970 00:00:00 GMT";
	    		return true;
	  		}
			
		}
	}
	return false;
		
}









