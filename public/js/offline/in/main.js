/**
 * Created by SolarZi on 2017/10/27.
 */
var otcSupport = 'Catatan : Silahkan Lakukan Pembayaran di Alfa Group (Alfamart, Alfamidi, LAWSON, DAN+DAN)';
var atmSupport = 'Catatan : Transaksi dapat juga melalui Mobile Banking. Support semua ATM bank.';
var code_605 = "Transaction Id repeat please exit and try again.";
var price,productId,transactionId,propsName,promotionId,payType,redirectUrl,prePage,customerId,msisdn,bankType,isShowLogo,offlineObj,status,description;
function setConfig($config){
    window.price = $config.price?$config.price:'';
    window.productId = $config.productId?$config.productId:'';
    window.transactionId = $config.transactionId?$config.transactionId:'';
    window.propsName = $config.propsName?$config.propsName:'';
    window.promotionId = $config.promotionId?$config.promotionId:'';
    window.payType = $config.payType?$config.payType:'';
    window.bankType = $config.bankType?$config.bankType:'';
    window.redirectUrl = $config.redirectUrl?$config.redirectUrl:'';
    window.prePage = $config.prePage?$config.prePage:'';
    window.customerId = $config.customerId?$config.customerId:'';
    window.msisdn = $config.msisdn?$config.msisdn:'';
    window.isShowLogo = $config.isShowLogo?$config.isShowLogo:'';
    window.offlineObj = $config.offlineObj?JSON.parse($config.offlineObj):'';
    window.status = $config.status?$config.status:'';
    window.description = $config.description?$config.description:'';
}
function init($config){
    setConfig($config);
    if(status!=201&&status.length>0){
        displayInfo(description);
    }
    $('.spanClass').empty().append(atmSupport);
    if (!isShowLogo) {
        $("#_logo").css("display","none");
    };
    triggerConfirm();
}
var isSelectBank = false;//是否选择了银行
$(document).on('click','.btn-bank',function(event){
    isSelectBank = true;
    var target = event.currentTarget;
    $('.btn-bank').css({'border-color':'#46b8da','background-color':'#a4d4e3'});
    $(target).css({'border-color':'red','background-color':'#31b0d5'});
    bankType = $(target).attr("bankType");
});
//错误提示框
function displayInfo(info){
    $('#modal-alert #infoID').empty().append(info);
    $('#modal-alert #alert_cont img').prop('src','/img/alert_error.png');
    $('#modal-alert').modal();
}
//关闭对话框
$(document).on('click','.modal-footer',function(event){
    $('#modal-alert').modal('hide');
});
//切换otc
$(document).on('click','#tab_otc',function(event){
    $('.phone-number').hide();
    $('#offlineFormID').show();
    $(".btn-group-banks").hide();
    $('.spanClass').empty().append(otcSupport);
    payType = "otc";
});
//切换ATM
$(document).on('click','#tab_atm',function(event){
    payType = "atm";
    $(".btn-group-banks").show();
    if (offlineObj.isStatic) {
        //静态VA
        $('.phone-number').show();
        $('#offlineFormID').hide();
    }else{
        //动态VA
        $('.phone-number').hide();
        $('#offlineFormID').show();
    }
    $('.spanClass').empty().append(atmSupport);
});

function triggerConfirm(){
    if('atm' == payType && !isLoad(transactionId,"offline")){
        console.log("go to atm");
        $("#tab_atm").trigger("click");
        //$("#btn_confirm_atm").trigger("click");
    }else if('otc' == payType && !isLoad(transactionId,"offline")){
        console.log("go to otc");
        //$('#btn_confirm_otc').trigger("cl");
        $('#btn_confirm_otc').trigger("click");
        //$("#tab_otc").trigger("click");
    }else{
        console.log("switch to atm");
        $("#tab_atm").trigger("click");
    }
}
function selectPayType(type){
    switch (type) {
        //atm
        case 'atm':
            $(".container-otc").hide();
            $(".container-atm").show();
            $("#offlineFormID #providerID").val('atm');
            $("#_sub-content .contentOfflineTop ul li:nth-child(1) a").css('color','black');
            $("#_sub-content .contentOfflineTop").css("backgroundImage","url(/img/atm_bj_640.png)");
            $("#myspanriceID").empty().append("Rp."+typeof(offlineObj) == "undefined"?0:offlineObj.atmFee);
            $("#myspantotallID").empty().append( "Rp." + (Number(price) + Number(typeof(offlineObj) == "undefined"?0:offlineObj.atmFee) )  ) ;
            bankInfo();
            break;
        //mini
        case 'otc':
            $(".container-otc").show();
            $(".container-atm").hide();
            $("#offlineFormID #providerID").val('otc');
            $("#_sub-content .contentOfflineTop ul li:nth-child(2) a").css('color','black');
            $("#_sub-content .contentOfflineTop").css("backgroundImage","url(/img/mini_bj_640.png)");
            $("#myspanriceID").empty().append("Rp."+typeof(offlineObj) == "undefined"?0:offlineObj.otcFee);
            $("#myspantotallID").empty().append( "Rp." + (Number(price) + Number(typeof(offlineObj) == "undefined"?0:offlineObj.otcFee) )  ) ;
            $("#_sub-content #offlineFormID .spanClass").empty().append('Catatan : Silahkan Lakukan Pembayaran di Alfa Group (Alfamart, Alfamidi, LAWSON, DAN+DAN)');
            break;
    }
}

//Catatan 内容
//bankInfo();
function bankInfo(){
    if(productId == 690 || productId == 691){
        $("#_sub-content #offlineFormID .spanClass").empty().append('Catatan : Transaksi dapat juga melalui Mobile Banking, untuk panduannya bisa di lihat di banner dalam aplikasi nonolive. Support semua ATM bank.');
    }else{
        $("#_sub-content #offlineFormID .spanClass").empty().append('Catatan : Transaksi dapat juga melalui Mobile Banking. Support semua ATM bank.');
    }
}
//请求
function doAction(obj){
    if (payType == "") {
        displayInfo('Silakan pilih bank untuk mengisi ulang.');
        return;
    };
    if ("atm"  != payType && "otc" != payType) {
        displayInfo('Silakan pilih bank untuk mengisi ulang.');
        return;
    }
    if (isCached(transactionId,payType)) {
        displayInfo('Anda tidak bisa mengirimkan permintaan yang sama.');
        return;
    }
    if("atm" == payType){
        if (bankType != 'permata' && bankType != 'mandiri' && bankType != 'bni') {
            displayInfo('Unsupport bank type.');
            return;
        }
        //校验手机号
        if(offlineObj.isStatic){
            var msisdn = $(".phone-number").val();
            if (msisdn.length <10 ) {
                displayInfo('Nomor telepon anda tidak support.');
                return;
            };
        }
        if (!isSelectBank) {
            if(confirm("您没有选择银行，将默认使用：permata") == false){
                return;
            }
        }
    }
    if(isCached(transactionId,'offline')){
        displayInfo(window.code_605);
        return;
    }
    var url = "provider="       + payType
        + "&productId="     + productId
        + "&promotionId="   + promotionId
        + "&transactionId=" + transactionId
        + "&price="			+ price
        + "&customerId="    + customerId
        + "&propsName="     + propsName
        + "&msisdn="		+ msisdn
        + "&bankType="		+ bankType
    showLoading();
    $.ajax({
        type: "GET",
        url: "/c=offline&a=offline?"+url,
        dataType: "json",
        // async:false,
        success: function(data){
            hideLoading();
            if (data.status == "201"||data.status == "200") {
                cache(transactionId);
                var host = "";
                if (payType == "atm") {
                    host = "/c=offline&a=atm?";
                }else if(payType == "otc"){
                    host = "/c=offline&a=otc?";
                }
                cache(transactionId,'offline');
                window.location.href=host
                    +"&status="+ data.status
                    + "&price="+price
                    + "&pre_page=" 			+ prePage
                    + "&productId="			+ productId
                    + "&redirect_url="		+ redirectUrl
                    + "&isDirect="	        + offlineObj.channel
                    + "&bankType="			+ bankType
                    + "&atmFee="			+ offlineObj.atmFee
                    + "&otcFee="			+ offlineObj.otcFee
                    + "&paymentcode="		+ data.payment_code;
            }else{
                displayInfo(data.description);
            }
        },
        error:function(){
            hideLoading();
            displayInfo('Request timeout, Please check your network.');
        }
    });
    return true;
}
function showLoading(){
    $('#modal-alert-loading').modal();
}

function hideLoading(){
    $('#modal-alert-loading').modal('hide');
}
//查询产品相应的配置信息
function checkIsTaxInclusive(){
    showLoading();
    $.ajax({
        type: "GET",
        url: "/c=offline&a=checkIsTaxInclusive?actionType=checkIsTaxInclusive&productId="+productId,
        dataType: "json",
        success: function(data){
            hideLoading();
            offlineObj = data;
            if (data.isStatic == 1) {
                isStatic = true;
            }else{
                isStatic = false;
            }
            triggerConfirm();
        },
        error:function(){
            hideLoading();

        }
    });
}

function isLoad(transactionId,action){
    var ca = document.cookie.split(';');
    for(var i=0; i<ca.length; i++) {
        var c = ca[i].trim();
        if (c == ("isLoaded="+action+ "_"+ transactionId)) {
            console.log("loaded:"+c);
            //document.cookie = "isLoaded=; expires=Thu, 01 Jan 1970 00:00:00 GMT";
            return true;
        }

    }
    var d = new Date();
    d.setTime(d.getTime()+(1*24*60*60*1000));
    var expires = "expires="+d.toGMTString();
    console.log('on load');
    document.cookie = "isLoaded="+action+"_"+ transactionId + "; " + expires;

    return false;
}