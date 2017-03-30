<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

/**
 * 后台公共文件
 * 主要定义后台公共函数库
 */

/* 解析列表定义规则*/

function get_list_field($data, $grid, $model)
{

    // 获取当前字段数据
    foreach ($grid['field'] as $field) {
        $array = explode('|', $field);
        $temp = $data[$array[0]];
        // 函数支持
        if (isset($array[1])) {
            $temp = call_user_func($array[1], $temp);
        }
        $data2[$array[0]] = $temp;
    }
    if (!empty($grid['format'])) {
        $value = preg_replace_callback('/\[([a-z_]+)\]/', function ($match) use ($data2) {
            return $data2[$match[1]];
        }, $grid['format']);
    } else {
        $value = implode(' ', $data2);
    }

    // 链接支持
    if (!empty($grid['href'])) {
        $links = explode(',', $grid['href']);
        foreach ($links as $link) {
            $array = explode('|', $link);
            $href = $array[0];
            if (preg_match('/^\[([a-z_]+)\]$/', $href, $matches)) {
                $val[] = $data2[$matches[1]];
            } else {
                $show = isset($array[1]) ? $array[1] : $value;
                // 替换系统特殊字符串
                $href = str_replace(
                    array('[DELETE]', '[EDIT]', '[MODEL]'),
                    array('del?ids=[id]&model=[MODEL]', 'edit?id=[id]&model=[MODEL]', $model['id']),
                    $href);

                // 替换数据变量
                $href = preg_replace_callback('/\[([a-z_]+)\]/', function ($match) use ($data) {
                    return $data[$match[1]];
                }, $href);

                $val[] = '<a href="' . U($href) . '">' . $show . '</a>';
            }
        }
        $value = implode(' ', $val);
    }
    return $value;
}

// 获取模型名称
function get_model_by_id($id)
{
    return $model = M('Model')->getFieldById($id, 'title');
}

// 获取属性类型信息
function get_attribute_type($type = '')
{
    // TODO 可以加入系统配置
    static $_type = array(
        'num' => array('数字', 'int(10) UNSIGNED NOT NULL'),
        'string' => array('字符串', 'varchar(255) NOT NULL'),
        'textarea' => array('文本框', 'text NOT NULL'),
        'datetime' => array('时间', 'int(10) NOT NULL'),
        'bool' => array('布尔', 'tinyint(2) NOT NULL'),
        'select' => array('枚举', 'char(50) NOT NULL'),
        'radio' => array('单选', 'char(10) NOT NULL'),
        'checkbox' => array('多选', 'varchar(100) NOT NULL'),
        'editor' => array('编辑器', 'text NOT NULL'),
        'picture' => array('上传图片', 'int(10) UNSIGNED NOT NULL'),
        'file' => array('上传附件', 'int(10) UNSIGNED NOT NULL'),
        'multi_images' => array('多图上传','varchar(255) NOT NULL'),
    );

    //$_type = hook('addFieldType',$_type,$filter=true);

    return $type ? $_type[$type][0] : $_type;
}

/**
 * 获取对应状态的文字信息
 * @param int $status
 * @return string 状态文字 ，false 未获取到
 * @author huajie <banhuajie@163.com>
 */
function get_status_title($status = null)
{
    if (!isset($status)) {
        return false;
    }
    switch ($status) {
        case -1 :
            return '已删除';
            break;
        case 0  :
            return '禁用';
            break;
        case 1  :
            return '正常';
            break;
        case 2  :
            return '待审核';
            break;
        default :
            return false;
            break;
    }
}

// 获取数据的状态操作
function show_status_op($status)
{
    switch ($status) {
        case 0  :
            return '启用';
            break;
        case 1  :
            return '禁用';
            break;
        case 2  :
            return '审核';
            break;
        default :
            return false;
            break;
    }
}

/**
 * 获取文档的类型文字
 * @param string $type
 * @return string 状态文字 ，false 未获取到
 * @author huajie <banhuajie@163.com>
 */
function get_document_type($type = null)
{
    if (!isset($type)) {
        return false;
    }
    switch ($type) {
        case 1  :
            return '目录';
            break;
        case 2  :
            return '主题';
            break;
        case 3  :
            return '段落';
            break;
        default :
            return false;
            break;
    }
}

/**
 * 获取配置的类型
 * @param string $type 配置类型
 * @return string
 */
function get_config_type($type = 0)
{
    $list = C('CONFIG_TYPE_LIST');
    return $list[$type];
}

/**
 * 获取配置的分组
 * @param string $group 配置分组
 * @return string
 */
function get_config_group($group = 0)
{
    $list = C('CONFIG_GROUP_LIST');
    return $group ? $list[$group] : '';
}

/**
 * select返回的数组进行整数映射转换
 *
 * @param array $map 映射关系二维数组  array(
 *                                          '字段名1'=>array(映射关系数组),
 *                                          '字段名2'=>array(映射关系数组),
 *                                           ......
 *                                       )
 * @author 朱亚杰 <zhuyajie@topthink.net>
 * @return array
 *
 *  array(
 *      array('id'=>1,'title'=>'标题','status'=>'1','status_text'=>'正常')
 *      ....
 *  )
 *
 */
function int_to_string(&$data, $map = array('status' => array(1 => '正常', -1 => '删除', 0 => '禁用', 2 => '未审核', 3 => '草稿')))
{
    if ($data === false || $data === null) {
        return $data;
    }
    $data = (array)$data;
    foreach ($data as $key => $row) {
        foreach ($map as $col => $pair) {
            if (isset($row[$col]) && isset($pair[$row[$col]])) {
                $data[$key][$col . '_text'] = $pair[$row[$col]];
            }
        }
    }
    return $data;
}

/**
 * zhl 2017-01-05 19:36 start
 *
 */

//商品标题及图标
function goods_id_to_goods_id_name(&$data)
{
    if ($data === false || $data === null) {
        return $data;
    }
    $data = (array)$data;
    foreach ($data as $k => $v) {
        $array = explode(',', $v['goods_id']);
        $where['id'] = array('in', $array);
        $goods_array = M('goods')->where($where)->getField('title,thumb', true);
        $goods_name_arr = array_keys($goods_array);
        $goods_thumb_arr = array_values($goods_array);
        $goods_name = implode(' + ', $goods_name_arr);
        $data[$k]['goods_id_name'] = $goods_name;
        if ($v['package_id'] != 0) {
            $array = explode(',', $v['package_id']);
            $where_['id'] = array('in', $array);
            $package = M('package')->where($where_)->getField('name,thumb', true);
            $package_name_arr = array_keys($package);
            $package_thumb_arr = array_values($package);
            $package_name = implode(' + ', $package_name_arr);
            if($data[$k]['goods_id_name']) {
                $data[$k]['goods_id_name'] = $data[$k]['goods_id_name'] .'+'. $package_name;
            } else {
                $data[$k]['goods_id_name'] = $package_name;
            }
            $data[$k]['goods_id_thumb'] = $package_thumb_arr[0];
        } else {
            $data[$k]['goods_id_thumb'] = $goods_thumb_arr[0];
        }
    }
    return $data;
}

//套餐/单品名
function package_id_to_package_id_name(&$data)
{
    if ($data === false || $data === null) {
        return $data;
    }
    $data = (array)$data;
    foreach ($data as $k => $v) {
        if ($v['package_id'] != 0) {
            $package = M('package')->where('id=' . $v['package_id'])->getField('name');
            $data[$k]['package_id_name'] = "套餐:" . $package;
        } else {
            $data[$k]['package_id_name'] = "非套餐商品";
        }
    }
    return $data;
}

//套餐是否最后一个
function shop_to_is_last(&$data)
{
    if ($data === false || $data === null) {
        return $data;
    }
    $data = (array)$data;
    foreach ($data as $k => $v) {
        $count = count($v['shop']);
        $i = 0;
        foreach ($v['shop'] as $key => $value) {
            $i++;
            if ($i != $count) {
                $data[$k]['shop'][$key]['is_last'] = 0;
            } else {
                $data[$k]['shop'][$key]['is_last'] = 1;
            }
        }
    }
    return $data;
}

//套餐是否最后一个
function is_last_to_is_last(&$data)
{
    if ($data === false || $data === null) {
        return $data;
    }
    $data = (array)$data;
    foreach ($data as $k => $v) {
        $count = count($v['goods']);
        $i = 0;
        foreach ($v['goods'] as $key => $value) {
            $i++;
            if ($i != $count) {
                $data[$k]['goods'][$key]['is_last'] = 0;
            } else {
                $data[$k]['goods'][$key]['is_last'] = 1;
            }
        }
    }
    return $data;
}

//支付状态
function pay_status_to_pay_status_name(&$data, $map = array('pay_status' => array(0 => '未支付', 1 => '已支付', 2 => '取消')))
{
    if ($data === false || $data === null) {
        return $data;
    }
    $data = (array)$data;
    foreach ($map as $col => $pair) {
        foreach ($data as $key => $value) {
            foreach ($pair as $k => $v) {
                if ($value[$col] == $k) {
                    $data[$key][$col . '_name'] = $v;
                }
            }
        }
    }
    return $data;
}

//快递状态
function logistics_status_to_logistics_status_name(&$data, $map = array('logistics_status' => array(0 => '未发货', 1 => '已发货', 2 => '已签收')))
{
    if ($data === false || $data === null) {
        return $data;
    }
    $data = (array)$data;
    foreach ($map as $col => $pair) {
        foreach ($data as $key => $value) {
            foreach ($pair as $k => $v) {
                if ($value[$col] == $k) {
                    $data[$key][$col . '_name'] = $v;
                }
            }
        }
    }
    return $data;
}

//用户对应用户积分等级id
function uid_to_score_level_rule(&$data)
{
    if ($data === false || $data === null) {
        return $data;
    }
    $data = (array)$data;
    $where['user_id'] = array('in', $data);
    $result = M('user_score_level')->where($where)->getField('user_id, user_score_level_rule_id', true);
    return $result;
}

//用户积分等级id对应等级名
function user_score_level_rule_id_to_user_score_level_rule_id_name(&$data)
{
    if ($data === false || $data === null) {
        return $data;
    }
    $data = (array)$data;
    $where['id'] = array('in', $data);
    $result = M('score_level_rule')->where($where)->getField('id, return_name, return_value, return_invite_user', true);
    return $result;
}

//用户id对应用户名
function user_id_to_nickname(&$data)
{
    if ($data === false || $data === null) {
        return $data;
    }
    $data = (array)$data;
    $where['uid'] = array('in', $data);
    $result = M('member')->where($where)->getField('uid, nickname', true);
    return $result;
}

//单一用户信息
function user_to_user_name(&$data)
{
    if ($data === false || $data === null) {
        return $data;
    }
    $data = (array)$data;
    $user = M('member')->find($data['uid']);
    $data['user_name'] = $user['nickname'];
    return $data;
}

//
//单一支付状态
function pay_status_one_to_pay_status_name_one(&$data, $map = array('pay_status' => array(0 => '未支付', 1 => '已支付', 2 => '取消')))
{
    if ($data === false || $data === null) {
        return $data;
    }
    $data = (array)$data;
    foreach ($map as $col => $pair) {
        foreach ($pair as $k => $v) {
            if ($data[$col] == $k) {
                $data[$col . '_name'] = $v;
            }
        }
    }
    return $data;
}

//单一套餐
function is_package_to_is_package_name(&$data, $map = array('is_package' => array(0 => '非套餐', 1 => '套餐')))
{
    if ($data === false || $data === null) {
        return $data;
    }
    $data = (array)$data;
    foreach ($map as $col => $pair) {
        foreach ($pair as $k => $v) {
            if ($data[0][$col] == $k) {
                $data[0][$col . '_name'] = $v;
            }
        }
    }
    return $data;
}

//单一订单信息：未支付
function no_pay_goods_id_to_goods_id_array(&$data)
{
    if ($data === false || $data === null) {
        return $data;
    }
    $data = (array)$data;
    $array = explode(',', $data['goods_id']);
    $where['id'] = array('in', $array);
    $goods_array = M('goods')->where($where)->getField('id,title,thumb', true);
    //$goods_array_values = array_values($goods_array);
    $where_goods_id['goods_id'] = array('in', $array);
    $where_goods_id['user_id'] = $data['user_id'];
    $goods_order_num = M('shopping')->where($where_goods_id)->getField('goods_id,goods_num,goods_price', true);
    foreach($goods_array as $k => $v) {
        $goods_array[$k]['num'] = $goods_order_num[$k]['goods_num'];
        $goods_array[$k]['goods_price'] = $goods_order_num[$k]['goods_price'];
    }
    $data['goods_datail'] = array_values($goods_array);
    $package = explode(',', $data['package_id']) ;
    $where_['id'] = array('in', $package);
    $package_array = M('package')->where($where_)->getField('id, name, thumb, package_code, package_prices', true);
    $where_package_id['package_id'] = array('in', $package);
    $where_package_id['user_id'] = $data['user_id'];
    $package_order_num = M('shopping')->where($where_package_id)->getField('package_id,goods_num,goods_price', true);
    foreach($package_array as $k => $v) {
        $package_array[$k]['num'] = $package_order_num[$k]['goods_num'];
        $package_array[$k]['goods_price'] = $package_order_num[$k]['goods_price'];
    }
    $data['package'] = array_values($package_array);
    return $data;
}

//单一订单信息：已支付
function pay_goods_id_to_goods_id_array(&$data)
{
    if ($data === false || $data === null) {
        return $data;
    }
    $data = (array)$data;
    $data['goods_datail'] = M('goods_order_shop')->where('order_id='.$data['id'])->select();
    return $data;
}

//
////收货人信息
//function receipt_address_id_to_receipt_address_array(&$data)
//{
//    if ($data === false || $data === null) {
//        return $data;
//    }
//    $data = (array)$data;
//    $receipt_address_id = json_decode($data['receipt_address_id']);
//    $goods_array = M('receipt_address')->where('id=' . $receipt_address_id)->getField('user_name,telephone,province_id,city_id,district_id,address');
//    $goods_array = array_values($goods_array);
//    $data['goods_datail'] = $goods_array[0];
//    return $data;
//}

//单一分类名
function class_to_class_name(&$data)
{
    if ($data === false || $data === null) {
        return $data;
    }
    $data = (array)$data;
    $map = array('id' => array('in', $data));
    $shop_class = M('shop_class')->where($map)->getField('id, name');
    return $shop_class;
}

/**
 * zhl 2017-01-05 19:36 end
 *
 */

/**
 * 动态扩展左侧菜单,base.html里用到
 * @author 朱亚杰 <zhuyajie@topthink.net>
 */
function extra_menu($extra_menu, &$base_menu)
{
    foreach ($extra_menu as $key => $group) {
        if (isset($base_menu['child'][$key])) {
            $base_menu['child'][$key] = array_merge($base_menu['child'][$key], $group);
        } else {
            $base_menu['child'][$key] = $group;
        }
    }
}

/**
 * 获取参数的所有父级分类
 * @param int $cid 分类id
 * @return array 参数分类和父类的信息集合
 * @author huajie <banhuajie@163.com>
 */
function get_parent_category($cid)
{
    if (empty($cid)) {
        return false;
    }
    $cates = M('Category')->where(array('status' => 1))->field('id,title,pid')->order('sort')->select();
    $child = get_category($cid);    //获取参数分类的信息
    $pid = $child['pid'];
    $temp = array();
    $res[] = $child;
    while (true) {
        foreach ($cates as $key => $cate) {
            if ($cate['id'] == $pid) {
                $pid = $cate['pid'];
                array_unshift($res, $cate);    //将父分类插入到数组第一个元素前
            }
        }
        if ($pid == 0) {
            break;
        }
    }
    return $res;
}

/**
 * 检测验证码
 * @param  integer $id 验证码ID
 * @return boolean     检测结果
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function check_verify($code, $id = 1)
{
    $verify = new \Think\Verify();
    return $verify->check($code, $id);
}

/**
 * 获取当前分类的文档类型
 * @param int $id
 * @return array 文档类型数组
 * @author huajie <banhuajie@163.com>
 */
function get_type_bycate($id = null)
{
    if (empty($id)) {
        return false;
    }
    $type_list = C('DOCUMENT_MODEL_TYPE');
    $model_type = M('Category')->getFieldById($id, 'type');
    $model_type = explode(',', $model_type);
    foreach ($type_list as $key => $value) {
        if (!in_array($key, $model_type)) {
            unset($type_list[$key]);
        }
    }
    return $type_list;
}

/**
 * 获取当前文档的分类
 * @param int $id
 * @return array 文档类型数组
 * @author huajie <banhuajie@163.com>
 */
function get_cate($cate_id = null)
{
    if (empty($cate_id)) {
        return false;
    }
    $cate = M('Category')->where('id=' . $cate_id)->getField('title');
    return $cate;
}

// 分析枚举类型配置值 格式 a:名称1,b:名称2
function parse_config_attr($string)
{
    $array = preg_split('/[,;\r\n]+/', trim($string, ",;\r\n"));
    if (strpos($string, ':')) {
        $value = array();
        foreach ($array as $val) {
            list($k, $v) = explode(':', $val);
            $value[$k] = $v;
        }
    } else {
        $value = $array;
    }
    return $value;
}

// 获取子文档数目
function get_subdocument_count($id = 0)
{
    return M('Document')->where('pid=' . $id)->count();
}


// 分析枚举类型字段值 格式 a:名称1,b:名称2
// 暂时和 parse_config_attr功能相同
// 但请不要互相使用，后期会调整
function parse_field_attr($string)
{
    if (0 === strpos($string, ':')) {
        // 采用函数定义
        return eval(substr($string, 1) . ';');
    }
    $array = preg_split('/[,;\r\n]+/', trim($string, ",;\r\n"));
    if (strpos($string, ':')) {
        $value = array();
        foreach ($array as $val) {
            list($k, $v) = explode(':', $val);
            $value[$k] = $v;
        }
    } else {
        $value = $array;
    }
    return $value;
}

/**
 * 获取行为数据
 * @param string $id 行为id
 * @param string $field 需要获取的字段
 * @author huajie <banhuajie@163.com>
 */
function get_action($id = null, $field = null)
{
    if (empty($id) && !is_numeric($id)) {
        return false;
    }
    $list = S('action_list');
    if (empty($list[$id])) {
        $map = array('status' => array('gt', -1), 'id' => $id);
        $list[$id] = M('Action')->where($map)->field(true)->find();
    }
    return empty($field) ? $list[$id] : $list[$id][$field];
}

/**
 * 根据条件字段获取数据
 * @param mixed $value 条件，可用常量或者数组
 * @param string $condition 条件字段
 * @param string $field 需要返回的字段，不传则返回整个数据
 * @author huajie <banhuajie@163.com>
 */
function get_document_field($value = null, $condition = 'id', $field = null)
{
    if (empty($value)) {
        return false;
    }

    //拼接参数
    $map[$condition] = $value;
    $info = M('Model')->where($map);
    if (empty($field)) {
        $info = $info->field(true)->find();
    } else {
        $info = $info->getField($field);
    }
    return $info;
}

/**
 * 获取行为类型
 * @param intger $type 类型
 * @param bool $all 是否返回全部类型
 * @author huajie <banhuajie@163.com>
 */
function get_action_type($type, $all = false)
{
    $list = array(
        1 => '系统',
        2 => '用户',
    );
    if ($all) {
        return $list;
    }
    return $list[$type];
}

/**
 * 发送短信验证码
 * @param  string $target 发送链接地址
 * @return string $data  发送链接参数
 * @author 张双 <15347096062@163.com>
 */
function send_phone_code($phone,$content){

    $target = "http://cf.51welink.com/submitdata/Service.asmx/g_Submit";
    $data = "sname=dlnjsh&spwd=njsh123456&scorpid=&sprdid=1012888&sdst=" . $phone . "&smsg=".$content;

    $url_info = parse_url($target);
    $httpheader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
    $httpheader .= "Host:" . $url_info['host'] . "\r\n";
    $httpheader .= "Content-Type:application/x-www-form-urlencoded\r\n";
    $httpheader .= "Content-Length:" . strlen($data) . "\r\n";
    $httpheader .= "Connection:close\r\n\r\n";
    //$httpheader .= "Connection:Keep-Alive\r\n\r\n";
    $httpheader .= $data;

    $fd = fsockopen($url_info['host'], 80);
    fwrite($fd, $httpheader);
    $gets = "";
    while(!feof($fd)) {
        $gets .= fread($fd, 128);
    }
    fclose($fd);
    if($gets != ''){
        $start = strpos($gets, '<?xml');
        if($start > 0) {
            $gets = substr($gets, $start);
        }
    }
    return xml_to_array($gets);
}

/**
 * 最简单的XML转数组
 * @param string $xmlstring XML字符串
 * @return array XML数组
 */
function xml_to_array($xmlstring)
{
    return json_decode(json_encode((array)simplexml_load_string($xmlstring)), true);
}
/**
 *
 */
function get_explode_post_time($var)
{
    $temp=explode("-",$var);
    return  $temp[0].$temp[1];
}