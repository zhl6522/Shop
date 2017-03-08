

/*右上角弹出菜单：*/
function hoverSub(li){
	var div=li.querySelector("div");
	/*获得计算后的样式集合，只读*/
	var style=getComputedStyle(div);
	div.style.display=style.display=="none"?
									"block":
									 "none";
}
function keepHover(div){
	var label=
		div.parentNode.querySelector("label");
	label.className=
		label.className=="rt"?"rt hover":"rt";
}
/*商品分类菜单*/
//鼠标进入category，弹出all_cate
function hoverAllCate(){
	var allCate=
		document.querySelector("#all_cate");
	var style=getComputedStyle(allCate);
	//如果现在隐藏，就显示出来
	allCate.style.display=
				style.display=="none"?"block":
		                               "none";
	//              否则就隐藏
}
//鼠标进入cate_item，弹出sub_cate_box
function hoverItem(div){
	var subDiv=
		div.querySelector(".sub_cate_box");
	var style=getComputedStyle(subDiv);
	//如果现在隐藏，就显示出来
	subDiv.style.display=
				style.display=="none"?"block":
		                               "none";
}
//保持cate_item的hover状态！
function keepH3Hover(div){
	var h3=div.parentNode.querySelector("h3");
	//判断h3中是否包含class属性
	h3.hasAttribute("class")?//如果包含就移除
		h3.removeAttribute("class"):
		h3.className="hover";//否则设置为hover
}
/*商品图片*/
/*小图片移动*/
const LI_WIDTH=62;
var moved=0;
function move(a){
	if(a.className.indexOf("_disabled")==-1){
		moved+=a.id=="btnLeft"?1:-1;
var ul=a.parentNode
	    .querySelector("#icon_list");
var style=getComputedStyle(ul);
ul.style.left=parseInt(style.left)
	    +(a.id=="btnLeft"?-LI_WIDTH:LI_WIDTH)
	    +"px";
var btnLeft=a.parentNode.querySelector("#btnLeft");
var btnRight=a.parentNode.querySelector("#btnRight");
if(moved==0){//右禁用
	btnRight.className="right_disabled";
}else if(moved==ul.children.length-5){
	btnLeft.className="left_disabled";
}else{
	btnRight.className="right";
	btnLeft.className="left";
}
	}
}

/*鼠标进入小图片时，切换对应中图片*/
//获得icon_list下所有img元素
var imgs=
  document.querySelectorAll("#icon_list img");
//遍历所有img元素，
for(var i=0;i<imgs.length;i++){
//   为每个img元素绑定onmouseover事件
	imgs[i].onmouseover=mChange;
}
function mChange(){//根据指定小图片修改中图片
//   小图片的src    -->...\product-s1.jpg
//   对应大图片的src-->...\product-s1-m.jpg
//   将大图片路径设置到mImg元素的src属性
	document.querySelector("#mImg").src=
		this.src.replace(".",".");
}

$('.choose_btn_append').click(function() {
	var num = parseInt($('.goods_num').val());
	if(num < 1) {
		alert('请选择合理的购买数量');
		return false;
	}
	var id = parseInt($('.goods_id').val());
	$.post("/index.php?s=/Admin/Shop/goshopping",{
			num:num,
			id:id
		}, function(data){console.log(data);
			if(data.status != 1) {
				alert(data.data);
			} else {
				//alert("/index.php?s=/Admin/Shop/order/id/"+id);
				location.href = "/index.php?s=/Admin/Shop/order/id/"+id+".html";//location.href实现客户端页面的跳转
			}
		},'json');
});


/*放大图：*/
//显示遮罩层和大图div
function showMask(){
	document.querySelector("#mask").style.display="block";
	var largeDiv=document.querySelector("#largeDiv");
	largeDiv.style.display="block";
	var mUrl=document.querySelector("#mImg").src;
	//...\product-s1-m.jpg
    //...\product-s1-l.jpg
	largeDiv.style.backgroundImage="url("
					+mUrl.replace("m.","l.")
					+")";
}
function hideMask(){
	document.querySelector("#mask").style.display="none";
	document.querySelector("#largeDiv").style.display="none";
}
//大图div背景随遮罩层移动
function zoom(){
	var e=window.event||arguments[0];
	//offsetX, offsetY 
	//-->相对于相对定位中的父元素边界
	//遮罩层宽高:175*175
	var mLeft=e.offsetX-175/2;
	mLeft>(350-175)&&(mLeft=(350-175));
	var mTop=e.offsetY-175/2;
	mTop>(350-175)&&(mTop=(350-175));
	var mask=document.querySelector("#mask");
	mask.style.left=mLeft+"px";
	mask.style.top=mTop+"px";
	var lDiv=document.querySelector("#largeDiv");
	lDiv.style.backgroundPosition=
		"-"+mLeft*2+"px "+"-"+mTop*2+"px";
		//background-position: x y
}