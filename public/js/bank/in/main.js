/**
 * Created by SolarZi on 2017/10/25.
 */
$(function() {
    /* hide show payment channel*/
    $('#select-paymentchannel').change(function(){
        $('.channel').hide();
        $('#' + $(this).val()).show();
    });

    var data = new Object();

    data.req_cc_field = 'cc_number';
    data.req_challenge_field = 'CHALLENGE_CODE_1';

    dokuMandiriInitiate(data);
});
jQuery(function($) {
    /**/
    $('.cc-number').payment('formatCardNumber');

    $.fn.toggleInputError = function(erred) {
        this.parent('.form-group').toggleClass('has-error', erred);
        return this;
    };
    $("#challenge_div_3").text(challenge3);
    $("#CHALLENGE_CODE_3").val(challenge3);
});
$(function(){
    //输入卡号超过10位时，显示下面空中
    $(document).on('keyup','#mandiriclickpay #cc_number',function(){
        getPamInfo();
    });

    //判断是否有值
    getPamInfo();
});
function getPamInfo(){
    var ccnumber = $("#cc_number").val();
    var cclength = ccnumber.length;
    if(cclength > 16){
        var challengeCode = ccnumber.substr(-16);
        $("#CHALLENGE_CODE_1").prop('value',challengeCode);
    }else{
        $("#CHALLENGE_CODE_1").prop('value','');
    }
}
/**
 * 显示加载动画
 */
function showLoading(){
    $('#modal-alert-loading').modal();
}
/**
 * 关闭加载动画
 */
function hideLoading(){
    $('#modal-alert-loading').modal('hide');
}