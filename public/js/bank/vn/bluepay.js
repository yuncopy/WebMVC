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
	
});

function showLoading(){
    $('#modal-alert-loading').modal();
}

function hideLoading(){
    $('#modal-alert-loading').modal('hide');
}



