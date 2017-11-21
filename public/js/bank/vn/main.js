;$(document).ready(function () {
	
	//显示其他
	$("#more-bank").click(function () {
		$(this).remove();
		$('.bankbox').css("display","");
		$('.hiddenbank').css("display","");
	});
	$(document).on('click','.wap form',function(event){
		var target = $(event.target);
		console.log(target);
		showLoading();
		//return false;
	});
	$('form').bind('submit', function (event) {
		event.preventDefault();
		showLoading();
		var data = $(this).serialize();
		$.ajax({
			type: "GET",
			async:true,
			url: "/c=bank&a=submit?"+data,
			dataType: "json",
			success: function(data){
				hideLoading();
				if(data.location){
					window.location.href = data.location;
				}else{
					displayInfo(data.msg+"("+data.status+")");
				}
			},
			error:function(error,error2,error3){
				hideLoading();
				console.log(error);
				console.log(error2);
				console.log(error3);
				displayInfo('Request timeout, Please check your network.');
			}
		});
	})
});





