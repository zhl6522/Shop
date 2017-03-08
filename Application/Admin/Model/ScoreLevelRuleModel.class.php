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

class ScoreLevelRuleModel extends Model {

	protected $_validate = array(
		array('source_value','require','等级积分满足条件值不能为空。'),
		array('source_value','/^[0-9]\d{0,}$/','等级积分满足条件值必须是大于0的数字。'),
		array('return_value', '/(^[1-9][0-9]?)$/', '相应等级折扣返利必须0-100之间',),
		array('return_invite_user', '/(^[0-9][0-9]?)$/', '返利给上线折扣必须0-100之间'),
	);

	protected $_auto = array(
		array('updated', NOW_TIME, self::MODEL_UPDATE),
		//array('status', '1', self::MODEL_UPDATE),
	);

}