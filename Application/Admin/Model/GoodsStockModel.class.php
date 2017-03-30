<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: yangweijie <yangweijiester@gmail.com> <code-tech.diandian.com>
// +----------------------------------------------------------------------

namespace Admin\Model;
use Think\Model;

/**
 * 插件模型
 * @author zhl <ahfuzl@126.com>
 */

class GoodsStockModel extends Model {

	protected $_validate = array(
		array('goods_num', '/[0-9]{1,11}$/', '商品库存不能为空', self::MODEL_BOTH),
	);

	protected $_auto = array(
		array('created', NOW_TIME, self::MODEL_INSERT),
		array('updated', NOW_TIME, self::MODEL_BOTH),
		array('status', '1', self::MODEL_BOTH),
	);

}