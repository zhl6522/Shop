
function hoverPop(li){
	var ul=li.querySelector('ul');
	var style=getComputedStyle(ul);
	ul.style.display=ul.style.display=='block'?'none':'block';		
}
$(function(){
	function cursor(){
		var index=0;
		var len=$('#one_main li').length;
		
		

		setInterval(function(){
			show(index);
			index++;
			if(index==len){
				index=0;
				$('#contain').css('background','#82deed');
			}else{
				$('#contain').css('background','#3b1000');
			}
		},3000);
		function show(index){			
			$('#one_main li').eq(index).fadeIn().siblings('li').fadeOut();
			
		}

	}
	cursor();


	//话费充值切换
	$('.toggle').click(function(e){
		if(e.preventDefault)
		{
			e.preventDefault();
		}else{
			e.returnValue=false;
		}
		var src=e.target||e.srcElement;
		var href=$(src).attr('href');
		if(!href){return;}
		//console.log(href);
		$('.current').removeClass('current');
		$(src).addClass('current');
		$('.right_table>table').attr('class','hidden');
		$(href).attr('class','show');
	});
})
