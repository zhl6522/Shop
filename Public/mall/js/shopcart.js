function add(obj) {
    var input_txt = parseInt($(obj).prev().val());
    input_txt++;
    $(obj).prev().val(input_txt);
    var tt = (input_txt * parseFloat($(obj).parent().parent().prev().text().slice(1))).toFixed(2);
    var good = $(obj).parent().parent().siblings().find('input[name="check_item[]"]').attr("data-id");
    var package = $(obj).parent().parent().siblings().find('input[name="check_item[]"]').attr("package-id");
    $(obj).parent().parent().next().html("￥" + tt);
    $(obj).parent().parent().siblings().find('input[name="check_item1"]').val(tt);
    $(obj).parent().parent().siblings().find('input[name="check_item[]"]').val(good + '_' + input_txt + '_' + tt + '_' + package);
    getCount();
}

function reduce(obj) {
    var input_txt = parseInt($(obj).next().val());
    if (input_txt != 1) {
        input_txt--;
        $(obj).next().val(input_txt);
        var tt = (input_txt * parseFloat($(obj).parent().parent().prev().text().slice(1))).toFixed(2);
        var good = $(obj).parent().parent().siblings().find('input[name="check_item[]"]').attr("data-id");
        var package = $(obj).parent().parent().siblings().find('input[name="check_item[]"]').attr("package-id");
        $(obj).parent().parent().next().html("￥" + tt);
        $(obj).parent().parent().siblings().find('input[name="check_item1"]').val(tt);
        $(obj).parent().parent().siblings().find('input[name="check_item[]"]').val(good + '_' + input_txt + '_' + tt + '_' + package);
        getCount();
    } else {
        $(obj).next().val(1)
    }
}
function getCount() {
    var conts = 0;
    var aa = 0;//商品数量
    //$('.shop_sec_checkbox .check_item').each(function(){
    $('.shop_sec_checkbox input[name="check_item[]"]').each(function () {
        if ($(this).prop('checked')) {
            for (var i = 0; i < $(this).length; i++) {
                conts += parseFloat($(this).next().val());
                aa += 1;
            }
        }
    })
    $('.shop_foot p span').html("￥" + conts);
    $('.shop_foot p .total').val(conts);
}
$(function () {
    $('.fullscreen_mask').css('height', $(document).height());
    $('.shop_tabs li').click(function () {
        var index = $(this).index();
        $(this).addClass('shop_tabs_cur').siblings().removeClass('shop_tabs_cur');
        $('.shop_child').eq(index).show().siblings().hide();
    })

    $('.shop_sec_detail input[name="check_item[]"]').click(function () {
        getCount();
    })

    $('#checkbox_all').click(function () {
        //alert($('#checkbox_all').attr('checked', true));
        if ($('#checkbox_all').attr('checked')) {
            $('.shop_child:first-child input[type="checkbox"]').removeAttr('checked');
        } else {
            $('.shop_child:first-child input[type="checkbox"]').attr('checked', 'true');
        }
        getCount();
    })
    //降价商品中的全选
    $('#checkbox_redc').click(function () {
        if ($('#checkbox_redc').attr('checked')) {
            $('.shop_child:last-child input[type="checkbox"]').removeAttr('checked');
        } else {
            $('.shop_child:last-child input[type="checkbox"]').attr('checked', 'true');
        }
        getCount();
    })

    function add() {
        var input_txt = parseInt($(this).prev().val());
        input_txt++;
        $(this).prev().val(input_txt);
        var tt = (input_txt * parseFloat($(this).parent().parent().prev().text().slice(1))).toFixed(2);
        $(this).parent().parent().next().html(tt);
        $(this).parent().parent().siblings().find('input[name="check_item[]"]').val(tt);
        getCount();
    }

    function reduce() {
        var input_txt = parseInt($(this).next().val());
        if (input_txt != 0) {
            input_txt--;
            $(this).next().val(input_txt);
            var tt = (input_txt * parseFloat($(this).parent().parent().prev().text().slice(1))).toFixed(2);
            $(this).parent().parent().next().html(tt);
            $(this).parent().parent().siblings().find('input[name="check_item[]"]').val(tt);
            getCount();
        } else {
            $(this).next().val(0)
        }
    }


    // var good_danjia=parseInt($('#goods_price').text().slice(1));
    //var goods_xiaoji=parseInt($('#shop_sec_total').text().slice(1));
    //$('.shop_sec_detail input[name="check_item1"]').val(goods_xiaoji);


    //$('#add_01').click(function(){
    //		var input_txt=parseInt($(this).prev().val());
    //		input_txt++;
    //		$(this).prev().val(input_txt);
    //		var tt=parseInt(input_txt*goodsPrice2).toFixed(2);
    //		$(this).parent().parent().next().html(tt);
    //		$(this).parent().parent().siblings().find('input[name="check_item"]').val(tt);
    //		getCount();
    //	})
    ////数量减少-
    //$('#reduce_01').click(function(){
    //	var input_txt=parseInt($(this).next().val());
    //	if(input_txt>1){
    //		input_txt--;
    //		$(this).next().val(input_txt);
    //		var tt=parseInt(input_txt*goodsPrice2).toFixed(2);
    //		$(this).parent().parent().next().html(tt);
    //		$(this).parent().parent().siblings().find('input[name="check_item"]').val(tt);
    //		getCount();
    //	}else{
    //		$(this).next().val(1)
    //	}
    //})

    //删除按钮执行逻辑
    $('.delete').click(function () {
        if (!confirm('确认该商品要移出购物车吗?')) {
            return false;
        }
        var good = $(this).parent().siblings().find('input[name="check_item[]"]').attr("data-id");
        var package = $(this).parent().siblings().find('input[name="check_item[]"]').attr("package-id");
        $(this).parent().parent().remove();
        $.post("/index.php?s=/Admin/Shop/deleteshopcart", {
            good: good,
            package: package
        }, 'json');
    });

})