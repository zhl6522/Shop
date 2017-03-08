function closePop(){
    $('.fullscreen_mask').hide();
}
function deleteItem(){
	 $(this).parent().parent().remove();
}
function pop(str){
    var html='<div class="fullscreen_mask">'
		+'<div class="tankuang">'
			+'<div class="dialog_title">'
				+'<img id="close" src="images/close.png" alt="" onclick="closePop()">'
			+'</div>'
			+'<div class="dialog_content">'
				+'<h4>'+str+'</h4>'
				+'<p class="dialog_btn"><a href="javascript:;" class="dialog_true" onclick="deleteItem()">确定</a><a href="javascript:;" class="dialog_cancle" onclick="closePop()">取消</a></p>'
			+'</div>'
		+'</div>'
	+'</div>';
    return html;
}