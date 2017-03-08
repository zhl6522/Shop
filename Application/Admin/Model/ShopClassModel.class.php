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

class ShopClassModel extends Model {

	protected $_validate = array(
		array('name', 'require', '分类名称不能为空', self::MODEL_BOTH),
		//array('name', '', '分类名称不能重复', 0, 'unique', self::MODEL_BOTH),
		array('name', '1,255', '分类名称长度不能超过255个字符', 0, 'length', self::MODEL_BOTH),
	);

	protected $_auto = array(
		array('created', NOW_TIME, self::MODEL_INSERT),
		array('updated', NOW_TIME, self::MODEL_BOTH),
		array('status', '1', self::MODEL_BOTH),
	);

	//获取树的根到子节点的路径
	public function getPath($id){
		$path = array();
		$nav = $this->where("id={$id}")->field('id,pid,name')->find();
		$path[] = $nav;
		if($nav['pid'] >1){
			$path = array_merge($this->getPath($nav['pid']),$path);
		}
		return $path;
	}
}