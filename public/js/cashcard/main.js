/**
 * Created by SolarZi on 2017/10/26.
 */
//隐藏弹框
$(document).on('click', '#modal-alert .modal-footer', function () {
    $('#modal-alert').modal('hide');
});
//错误提示框
function displayInfo(info) {
    $('#modal-alert #infoID').empty().append(info);
    $('#modal-alert #alert_cont img').prop('src', '/img/alert_error.png');
    $('#modal-alert').modal();
}
var ct,lang,redirectUrl,cardNo,serialNo,productId,promotionId,provider,propsName,customerId,transactionId,status,description,isShowLogo;
function setConfig($config){
    window.ct = $config.ct?$config.ct:'';
    window.lang = $config.lang?JSON.parse($config.lang):'';
    window.redirectUrl = $config.redirectUrl?$config.redirectUrl:'';
    window.cardNo = $config.cardNo?$config.cardNo:'';
    window.serialNo = $config.serialNo?$config.serialNo:'';
    window.productId = $config.productId?$config.productId:'';
    window.promotionId = $config.promotionId?$config.promotionId:'';
    window.provider = $config.provider?$config.provider:'';
    window.propsName = $config.propsName?$config.propsName:'';
    window.customerId = $config.customerId?$config.customerId:'';
    window.transactionId = $config.transactionId?$config.transactionId:'';
    window.status = $config.status?$config.status:'';
    window.description = $config.description?$config.description:'';
    window.isShowLogo = $config.isShowLogo?$config.isShowLogo:'';
}
function init($config){
    setConfig($config)
    if(status.length>0){
        displayInfo(description);
    }
    if (!isShowLogo) {
        $("#_logo img").hide();
    }
    selectTelco(provider);
}
function selectTelco(id) {
    if(id!=provider){
        $.ajax({
            type: "GET",
            async:false,
            url: "/c=cashcard&a=index?"+"productId="+productId+"&transactionId="+transactionId+"&provider="+id+"&promotionId="+promotionId+"&propsName="+propsName+"&redirect_url="+redirectUrl+"&customerId="+customerId,
            dataType: "json",
            success: function(data){
                setConfig(data);
                $("#inptTextId").empty().html(data.input);
            },
            error:function(error,error2,error3){
                console.log(error);
                console.log(error2);
                console.log(error3);
                hideLoading();
                displayInfo('Request timeout, Please check your network.');
            }
        });
    }
    var obj = $("#_sub-content .contentTopthb .topclass ul li a");
    obj.css('color', 'darkgray');
    var num = obj.length;
    for (var i = 1; i <= num; i++) {
        var a = $("#_sub-content .contentTopthb .topclass ul li a").css('border', '0px');
    }
    $("#th_guide_bluecoins").hide();
    if(provider.toLowerCase() == 'bluecoins'){
        $("#th_guide_bluecoins").show();
    }
    showLoading();
    //修改运营商的图标
    replaceOperPic(id.toLowerCase()+'_icon.png');
    //选中的运营商底部添加下划线
    $("#"+id).attr('style', 'color:black;border-bottom:2px solid #309CFF;');
    //隐藏loading
    hideLoading();
}
//提交表单
function submit() {
    var cardNo = "";
    var serialNo = "";
    if (provider == 'Dtac') {
        // dtac
        if ($("#abcdId").prop('value').length == 0) {
            //卡号不能为空
            displayInfo(lang.cardno_empty);
            return false;
        }
        if ($("#efghId").prop('value').length == 0) {
            //卡密不能为空
            displayInfo(lang.serialno_empty);
            return false;
        }
        cardNo = $("#abcdId").prop('value') + $("#efghId").prop('value');
    } else if ((provider == 'Mobifone') || (provider == 'Vinaphone') || (provider == 'Viettel') || (provider == 'Vtc') || (provider == 'Megacard')) {
        // 表单验证 越南的卡号
        if ($.trim($("#input_card_no").val()).length == 0) {
            displayInfo(lang.cardno_empty);
            return false;
        }
        // 表单验证  越南卡密
        if ($.trim($("#input_serial_no").val()).length == 0) {
            displayInfo(lang.serialno_empty);
            return false;
        }
        cardNo = $.trim($("#input_card_no").val());
        serialNo = $.trim($("#input_serial_no").val());
    } else {
        // 表单验证 卡号
        if ($.trim($("#input_card_no").val()).length == 0) {
            displayInfo(lang.cardno_empty);
            return false;
        }
        cardNo = $.trim($("#input_card_no").val());
    }
    var url = "provider=" + provider
        + "&cardNo=" + cardNo
        + "&serialNo=" + serialNo
        + "&productId=" + productId
        + "&promotionId=" + promotionId
        + "&transactionId=" + transactionId
        + "&customerId=" + customerId
        + "&propsName=" + propsName
        + "&ui=none";
    showLoading();
    $.ajax({
        type: "GET",
        url: "/c=cashcard&a=index?" + url,
        dataType: "json",
        success: function (data) {
            hideLoading();
            var price = "&price=";
            if (data.status == "200") {
                price = price + data.price;
            }
            if (redirectUrl == "" || typeof(redirectUrl) == "undefined") {
                window.location.href = "/c=cashcard&a=response?"
                    + "status=" + data.status
                    + "&code=" + data.status
                    + price
                    + "&transactionId=" + transactionId
                    + "&description=" + data.description
                    + "&cardNo=" + cardNo;
            } else {
                window.location.href = redirectUrl + "?status=" + data.status
                    + price
                    + "&transactionId=" + transactionId
                    + "&code=" + data.status
                    + "&description=" + data.description
                    + "&cardNo=" + cardNo;
            }
        },
        error: function () {
            hideLoading();
            displayInfo('Request timeout, Please check your network.');
        }
    });
    return false;
    //$("#testFormID").submit();
}
//更换运营商图标
function replaceOperPic(picname) {
    $("#_sub-content .contentpicthb img").prop('src', '/img/cashcard/' + picname);
}
function showLoading(){
    $('#modal-alert-loading').modal();
}
function hiddenInfo(redirectUrl=''){
    $('#modal-alert').modal('hide');
    if (redirectUrl != "") {
        window.location.href = redirectUrl;
    };
}
function hideLoading(){
    $('#modal-alert-loading').modal('hide');
}
function chooseMore() {
    $("#contentthbID").show();
    $(".contentpicthb").hide();
    $(".contentbottomthb").hide();
    $(".topclass").hide();
    $(".bottomclass").show();
}
function closeCountryBut(){
    $(".contentpicthb").show();
    $(".contentbottomthb").show();
    $(".topclass").show();
    $("#contentthbID").hide();
    $(".bottomclass").hide();
}