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

class GoodsModel extends Model {

	protected $_validate = array(
		array('title', 'require', '商品名称不能为空', self::MODEL_BOTH),
		array('title', '', '商品名称不能重复', 0, 'unique', self::MODEL_INSERT),
		array('title', '1,255', '分类名称长度不能超过255个字符', 0, 'length', self::MODEL_BOTH),
		array('unit_price', 'require', '单价不能为空', self::MODEL_BOTH),
		array('commodity_name', 'require', '附属信息的商品名称不能为空', self::MODEL_BOTH),
		array('brand', 'require', '附属信息的品牌不能为空', self::MODEL_BOTH),
		array('net_content', 'require', '附属信息的商品净含量不能为空', self::MODEL_BOTH),
		array('origin', 'require', '附属信息的商品产地不能为空', self::MODEL_BOTH),
		array('type', 'require', '附属信息的商品类型不能为空', self::MODEL_BOTH),
		array('specifications', 'require', '附属信息的商品规格不能为空', self::MODEL_BOTH),
		array('flavor', 'require', '附属信息的香型不能为空', self::MODEL_BOTH),
		array('describe', 'require', '附属信息的售后保障不能为空', self::MODEL_BOTH),
		array('introduce', 'require', '附属信息的商品介绍不能为空', self::MODEL_BOTH),
		array('goods_num', '/[0-9]{1,11}$/', '商品库存不能为空', self::MODEL_BOTH),
	);

	protected $_auto = array(
		array('created', NOW_TIME, self::MODEL_INSERT),
		array('updated', NOW_TIME, self::MODEL_BOTH),
		array('status', '1', self::MODEL_BOTH),
	);

}