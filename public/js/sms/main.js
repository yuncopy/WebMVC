/**
 * Created by SolarZi on 2017/10/23.
 */
var os, productId, price, telcos, transactionId, promotionId,propsName, ct, telco, isHwChannel, isShowLogo, status, description,lang,href,bank;
var telcoPrefixPlaceHolder ={
    'telkomsel':'mendukung:0811,0812,0813,0821,0822,0823,0852,0853',
    'xl':'mendukung:0817,0818,0819,0831,0832,0838,0859,0877,0878,0879',
    'indosat':'mendukung:0814,0815,0816,0855,0856,0857,0858',
    'hutchison':'mendukung:0895,0896,0897,0898,0899'
};
var placeHolder = {
    'indosat': "(contoh:0857xxxxx) masukkan nomor hp anda",
    'xl': "(contoh:0818xxxxx) masukkan nomor hp anda",
    'hutchison': "(contoh:0898xxxxx) masukkan nomor hp anda",
    'smartfren': "(contoh:0881xxxxx) masukkan nomor hp anda",
    'telkomsel': "(contoh:0811xxxxx) masukkan nomor hp anda"
};
var descriptions = {
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
/**
 * 初始化
 */
function init($config){
    setConfig($config);
    if(status!=201&&status.length>0){
        displayInfo(description);
    }
    if (!isShowLogo) {
        $("#_logo").css("display","none");
    }
    changeTelcos(telco)
}
/**
 * 设置配置
 * @param $config
 */
function setConfig($config){
    window.os = $config.os?$config.os:'';
    window.productId = $config.productId?$config.productId:'';
    window.price = $config.price?$config.price:'';
    window.telcos = JSON.stringify($config.telcos?$config.telcos:[]);
    window.transactionId = $config.transactionId?$config.transactionId:'';
    window.promotionId = $config.promotionId?$config.promotionId:'';
    window.ct = $config.ct?$config.ct:'';
    window.telco = $config.telco?$config.telco:'';
    window.isHwChannel = $config.isHwChannel?$config.isHwChannel:'';
    window.isShowLogo = $config.isShowLogo?$config.isShowLogo:'';
    window.status = $config.status?$config.status:'';
    window.description = $config.description?$config.description:'';
    window.href = $config.href?$config.href:'';
    window.propsName = $config.propsName?$config.propsName:'';
    window.lang = $config.lang?$config.lang:'';
    window.bank = $config.bank?$config.bank:'';
}
/**
 * 选择运营商
 * @param $telco
 * @param $k
 */
function changeTelcos($telco,$k=0){
    showLoading();
    if($telco!=telco){
        $.ajax({
            type: "GET",
            async:false,
            url: "/c=sms&a=index?"+"productId="+productId+"&transactionId="+transactionId+"&price="+price+"&telco="+$telco+"&promotionId="+promotionId+"&propsName="+propsName+"&bank="+bank,
            dataType: "json",
            success: function(data){
                setConfig(data);
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
    var obj = $(this);
    obj.css('color','darkgray');
    //根据用户选择的运营商切换运营商。
    var tabBgName = "tab_telco1_"+$k;
    if (JSON.parse(telcos).length == '4') {
        tabBgName = "tab_telco2_"+$k;
    }
    $(".input-msisdn").val("");
    $("#btn_submit").attr("href","#");
    $("#_sub-content .contentpic img").prop('src','/img/sms/telco_'+$telco+'.png');
    //修改选项卡背景图片
    $("#_sub-content .contentTopweb").css("backgroundImage","url(/img/sms/"+tabBgName+".png)");
    $("#_sub-content .contentTopweb ul li:nth-child("+$k+") a").css('color','black');
    //华为通道要显示填入手机号码的表单
    if(isHwChannel){
        $('.input-msisdn').css("display","block");
        var placeholder = getPlaceHolder($telco);
        $('.input-msisdn').attr("placeholder",placeholder);
    }else{
        $('.input-msisdn').css("display","none");
    }
    if (!isHwChannel) {
        //直连
        $("#phone_prefix").empty().append("");
        $("#btn_submit").attr("href",HtmlUtil.htmlDecode(href));
        $("#btn_submit").removeAttr("onclick");
    }else{
        //华为通道
        $("#phone_prefix").empty().append(telcoPrefixPlaceHolder[telco]);
        $("#btn_submit").removeAttr("href");
        $("#btn_submit").attr("onclick","getSmsContent()");
    }
    hideLoading();
}
function getSmsContent(){
    showLoading();
    var msisdn = $('.input-msisdn').val();
    if (msisdn == "") {
        hideLoading();
        var desc = 'msisdh  empty.';
        displayInfo(desc);
        return;
    }
    $.ajax({
        type: "GET",
        async:true,
        url: "/c=sms&a=genSmsContent?"+"productId="+productId+"&transactionId="+transactionId+"&price="+price+"&telco="+telco+"&msisdn="+msisdn+"&promotionId="+promotionId+"&propsName="+propsName+"&bank="+bank,
        dataType: "json",
        success: function(data){
            hideLoading();
            if(data.status == 201&&data.href.length>0){
                displayInfoForSms(data.href);
            }else if(data.status == 201&&data.href.length==0){
                displayInfo(data.description+"("+data.status+")",'success')
            }else{
                displayInfo( data.description +"("+ data.status+ ")");
            }
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
/**
 * 获取placeholder
 * @param telco
 * @returns {*}
 */
function getPlaceHolder(telco){
    return placeHolder[telco];
}

window.onpageshow = function(event) {
    if (event.persisted) {
        window.location.reload()
    }
};
//隐藏弹框
function hiddenInfo(redirectUrl=''){
    $('#modal-alert').modal('hide');
    if (redirectUrl != "") {
        window.location.href = redirectUrl;
    };
}
function displayInfoForSms(href){
    $("#modal_confirm").attr("href",href);
    $("#modal_confirm").empty().append("Send now");
    $('#modal-alert #infoID').empty().append("Please click confirm button to send message");
    $('#modal-alert #alert_cont img').prop('src','/img/success.png');
    $('#modal-alert').modal();
    $('#modal-alert').on('hidden.bs.modal', function () {

    });
}

