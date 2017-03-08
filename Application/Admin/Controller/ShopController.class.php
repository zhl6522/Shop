<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Admin\Controller;

use User\Api\UserApi as UserApi;

/**
 * 后台首页控制器
 * @author zhl <ahfuzl@126.com>
 */
class ShopController extends AdminController
{

    /**
     * 后台商品页
     * @author zhl <ahfuzl@126.com>
     */
    public function index()
    {
        //if (UID) {
        $id = I('get.id');
        if ($id) {
            $goods = M('goods')->where('status=1 && id=' . $id)->getField('id,title,thumb,unit_price,class_id');
            if (!$goods) {
                $this->success('访问商品不存在，可能已下架', U('Index/index'));
            }
            $goods = parent::array_value($goods);
            $goods_detail = M('goods_detail')->where('goods_id = ' . $goods[0]['id'])->getField('id,pic_1,pic_2,pic_3,pic_4,pic_5,commodity_name,brand,net_content,origin,type,specifications,flavor,created,describe,introduce');
            $goods_detail = parent::array_value($goods_detail);
            $shop_class = M('shop_class')->where('id=' . $goods[0]['class_id'])->getField('id, pid, name');
            $classs = parent::array_value($shop_class);
            if ($classs[0]['pid'] != 0) {
                $class = self::get_pid($classs[0]['pid']);
            }
            $shop_detail = self::get_id($classs[0]['pid']);
            $class[0]['class'] = $classs;
            $menus = M('shop_class')->where('status=1 && pid=0')->getField('id,type,name,level');
            $list = M('goods')->where('status = 1')->order('id desc')->limit(4)->getField('id,title,thumb,unit_price');
            $this->assign('_menus', $menus);
            $this->assign('_class', $class);
            $this->assign('_goods', $goods);
            $this->assign('_goods_detail', $goods_detail);
            $this->assign('_shop_detail', $shop_detail);
            $this->assign('_list', $list);
            $this->meta_title = '商品详情';
            $this->display();
        } else {
            $this->redirect('Index/index');
        }
    }

    public function package_order()
    {//购买套餐
        $package_id = I('post.package_id');
        $package = M('package')->where('id=' . $package_id)->getField('package_prices');
        $goods = M('goods')->where('status=1 && package_id=' . $package_id)->getField('id,package_price,title', true);
        $good_ids = parent::get_ids($package, 'id');
        $map['status'] = 1;
        $map['goods_id'] = array('in', $good_ids);
        $map['goods_num'] = 0;
        $goods_stock = M('goods_stock')->where($map)->getField('id,goods_id,goods_num');
        if ($goods_stock) {
            $this->success('库存不足', U('Index/index'));
        }
        $model = new \Think\Model();
        $model->startTrans();
        $uid = parent::get_uid();
        $shopping = M('shopping');
        $data = $shopping->create();
        $shopping->user_id = $uid;
        $shopping->goods_id = 0;
        $shopping->package_id = $package_id;
        $shopping->goods_num = 1;
        $shopping->goods_price = $package;
        $shopping->created = time();
        $stock_id = $shopping->add();
        if ($stock_id) {
            $model->commit();
            $this->success("套餐添加成功", U('Shop/shopcart'));
        } else {
            $model->rollback();
            $this->success('购买失败', U('Index/index'));
        }
        foreach ($package as $k => $v) {

        }
    }

    public function goshopping()
    {//单商品购买
        $num = I('post.num');
        if($num < 1) {
            $num = 1;
        }
        $id = I('id');
        $msg = '';
        $status = '';
        if ($num && $id) {
            $goods_price = M('goods')->where('status=1 && id=' . $id)->getField('unit_price');
            $model = new \Think\Model();
            $model->startTrans();
            $goods_stock = M('goods_stock')->where('status = 1 && goods_id = ' . $id)->getField('id,goods_id,goods_num');
            $goods_stock = parent::array_value($goods_stock);
            if ($goods_stock[0]['goods_num'] >= $num) {
                $uid = parent::get_uid();
                $shopping = M('shopping');
                $data = $shopping->create();
                $exist = $shopping->where('user_id = ' . $uid . ' && goods_id = ' . $id)->getField('id,goods_num,goods_price');
                if ($exist) {
                    $price = $num * $goods_price;
                    $exist = parent::array_value($exist);
                    $shopping->id = $exist[0]['id'];
                    $shopping->goods_num = $exist[0]['goods_num'] + $num;
                    $shopping->goods_price = $exist[0]['goods_price'] + $price;
                    if ($shopping->save() !== false) {
                        $model->commit();
                        $msg = '更新成功';
                        $status = 1;
                    } else {
                        $model->rollback();
                        $msg = '数据库异常';
                        $status = -2;
                    }
                } else {
                    if ($data) {
                        $shopping->id = null;
                        $shopping->user_id = $uid;
                        $shopping->goods_id = $id;
                        $shopping->goods_num = $num;
                        $shopping->goods_price = $num * $goods_price;
                        $shopping->created = time();
                        $stock_id = $shopping->add();
                        if ($stock_id) {
                            $model->commit();
                            $msg = '写入成功';
                            $status = 1;
                        } else {
                            $model->rollback();
                            $msg = '数据库异常';
                            $status = -1;
                        }
                    } else {
                        $msg = '服务器问题';
                        $status = -3;
                    }
                }
            } else {
                $msg = '库存不足';
                $status = 0;
            }
        } else {
            $msg = 'f';
            $status = -3;
        }
        $array = array(
            'data' => $msg,
            'status' => $status
        );
        echo json_encode($array);
    }

    public function order()
    {
        $id = I('get.id');
        if (!$id) {
            $this->redirect('Index/index');
        }
        $shopping = M('shopping');
        $uid = parent::get_uid();
        $exist = $shopping->where('user_id = ' . $uid . ' && goods_id = ' . $id)->getField('id,goods_num,goods_price');
        if (!$exist) {
            $this->success('没有添加购物车此商品', U('Index/index'));
        }
        $goods = M('goods')->where('id=' . $id)->getField('id,thumb,title');
        $goods = parent::array_value($goods);
        $list = M('goods')->where('status=1')->order('id desc')->limit(10)->getField('id,title,thumb,unit_price');
        $this->assign('_goods', $goods);
        $this->assign('_list', $list);
        $this->meta_title = '添加购物车成功';
        $this->display();
    }

    public function deleteshopcart()
    {
        $good = I('post.good');
        $package = I('post.package');
        $uid = parent::get_uid();
        $shopping = M('shopping');
        if ($good) {
            $wh['user_id'] = $uid;
            $wh['goods_id'] = $good;
            $list = M('shopping')->where($wh)->delete();
        }
        if ($package) {
            $wh_['user_id'] = $uid;
            $wh_['package_id'] = $package;
            $list = M('shopping')->where($wh_)->delete();
        }
    }

    public function shopcart()
    {
        if ($_POST) {
            $check_item = I('post.check_item');
            if (!$check_item) {
                $this->success('请选择商品');exit;
            }
            foreach ($check_item as $k => $v) {
                $check_item[$k] = explode('_', $v);
            }
            $ids = parent::get_ids($check_item, '0');
            $goods_nums = parent::get_ids($check_item, '1');
            $package_ids = parent::get_ids($check_item, '3');
            if ($ids) {
                $id_string = implode(',', $ids);
            } else {
                $id_string = 0;
            }
            if($goods_nums) {
                $goods_num = array_sum($goods_nums);
            }
            if ($package_ids) {
                $package_id_string = implode(',', $package_ids);
            } else {
                $package_id_string = 0;
            }
            $total = I('post.total');
            $uid = parent::get_uid();
            $user_score_level = parent::get_user_score_level_rule_id($uid);
            $order_code = parent::get_order_code($uid);
            $uid = parent::get_uid();
            $model = new \Think\Model();
            //$model->startTrans();
            $shopping = M('shopping');
            foreach ($check_item as $k => $v) {
                $shopping_data['goods_num'] = $v[1];
                $shopping_data['goods_price'] = $v[2];
                $shopping->where('user_id = ' . $uid . ' && goods_id=' . $v[0])->save($shopping_data);
            }
            $order = M('goods_order');
            $data = $order->create();
            if ($data) {
                $order->order_code = $order_code;
                $order->user_id = $uid;
                $order->user_score_level_rule_id = $user_score_level;
                $order->goods_id = $id_string;
                $order->package_id = $package_id_string;
                $order->total = $total;
                $order->order_num = $goods_num;
                $order->pay_status = 0;
                $order->created = time();
                $order->status = 1;
                $order->month = date('Ym', time());
                $stock_id = $order->add();
                if ($stock_id) {
                    $order_detail = M('goods_order_detail');
                    $order_detail_data['order_id'] = $stock_id;
                    $order_detail_data['created'] = time();
                    $order_detail_data['status'] = 1;
                    $stock_detail_id = $order_detail->add($order_detail_data);
                    if ($stock_detail_id) {
                        $model->commit();
                        $this->redirect('Shop/shoporder');
                    } else {
                        $model->rollback();
                        $this->success('提交失败', U('Index/index'));
                    }
                } else {
                    $model->rollback();
                    $this->success('提交失败', U('Index/index'));
                }
            } else {
                $model->rollback();
                $this->success('提交失败', U('Index/index'));
            }
        } else {
            $shopping = M('shopping');
            $uid = parent::get_uid();
            $exist = $shopping->where('user_id = ' . $uid)->getField('id,goods_id,goods_num,goods_price,package_id');
            if (!$exist) {
                $this->success('购物车没有商品', U('Index/index'));
            }
            $package_ids = parent::get_ids($exist, 'package_id');
            if (count($package_ids)) {
                $where['id'] = array('in', $package_ids);
                $where['status'] = 1;
                $package = M('package')->where($where)->getField('id, name, thumb, package_prices', true);
                foreach ($package as $k => $v) {
                    $package[$k]['title'] = $v['name'];
                    $package[$k]['unit_price'] = $v['package_prices'];
                }
            }
            $ids = parent::get_ids($exist, 'goods_id');
            if (count($ids)) {
                $map['id'] = array('in', $ids);
                $goods = M('goods')->where($map)->getField('id,thumb,title,unit_price');
            }
            foreach ($exist as $k => $v) {
                foreach ($goods as $key => $value) {
                    if ($v['goods_id'] == $key) {
                        $exist[$k]['goods'] = $value;
                    }
                }
                foreach ($package as $key => $value) {
                    if ($v['package_id'] == $key) {
                        $exist[$k]['goods'] = $value;
                    }
                }
            }
            $this->assign('_list', $exist);
            $this->meta_title = '我的购物车';
            $this->display();
        }
    }

    public function shoporder()
    {
        $uid = parent::get_uid();

        $list = M("address")->where("uid = " . $uid)->select();

        $res = array();
        foreach ($list as $v) {
            if (!in_array($v['province_id'], $res)) {
                array_push($res, $v['province_id']);
            }
            if (!in_array($v['city_id'], $res)) {
                array_push($res, $v['city_id']);
            }
            if (!in_array($v['district_id'], $res)) {
                array_push($res, $v['district_id']);
            }
        }
        $where['id'] = array("in", $res);
        $list_address = M("district")->field("name,id")->where($where)->select();

        foreach ($list_address as $vales) {
            $arr[$vales['id']] = $vales['name'];
        }

        foreach ($list as &$vs) {
            $vs['province_name'] = $arr[$vs['province_id']];
            $vs['city_name'] = $arr[$vs['city_id']];
            $vs['district_name'] = $arr[$vs['district_id']];
            if ($vs['status'] == 1) {
                $vs['status_text'] = "默认地址";
            } else {
                $vs['status_text'] = "";
            }
        }
        if ($_POST) {
            $type = parent::get_type();
            if($type == 1 || $type == 5) {
                $this->success('为防止刷单，管理员/财务不允许购买商品');exit;
            }
            $address = I('post.address');
            $order_code = I('post.order_code');
            $map['user_id'] = $uid;
            $map['status'] = 1;
            $goods_order_m = M('goods_order');
            $user_score_m = M('user_score');
            $goods_order_detail = M('goods_order_detail');
            $goods_order_shop = M('goods_order_shop');
            $member = M('member');
            $user_score_log = M('user_score_log');
            $score_level_rule = M('score_level_rule');
            $user_invite_user = M('user_invite_user');
            if (!$address) {
                $this->success('请选择地址');exit;
            }
            $goods_order = $goods_order_m->where('order_code=' . $order_code)->getField('id,total,goods_id,order_code,package_id');
            $goods_order = parent::array_value($goods_order);
            $score_level_rule_find = $score_level_rule->where('id=1')->getField('id,source_value,return_name,return_value,return_invite_user', true);
            if(parent::get_type() ==3) {
                $total = $score_level_rule_find['1']['return_value']*$goods_order[0]['total']/100;
            } else {
                $total = $goods_order[0]['total'];
            }

            $order_id = $goods_order[0]['id'];
            $user_score = $user_score_m->where($map)->getField('id,user_id,score');
            $user_score = self::array_value($user_score);
            $model = new \Think\Model();
            $model->startTrans();
            if ($user_score[0]['score'] >= $total) {
                $score = $user_score[0]['score'] - $total;
                $user_score[0]['id'];
                $user_score_m->score = $score;
                $user_score_m->updated = time();
                /*消费累计总额SATRT*/
                $member->score = $member->score + $total;
                $member->updated = time();
                /*消费累计总额END*/
                /*日志记录START*/
                $data_user_score_log['user_id'] = $uid;
                $data_user_score_log['self_year_month_day'] = date('Y-m-d', time());
                $data_user_score_log['score'] = $total;
                $data_user_score_log['content'] = '用户购买消费' . $total . '元';
                $data_user_score_log['type'] = 3;
                $data_user_score_log['created'] = time();
                $data_user_score_log['status'] = 1;
                /*日志记录END*/
                if ($user_score_m->where('id=' . $user_score[0]['id'])->save() !== false && $member->where('uid=' . $uid)->save() !== false && $user_score_log->add($data_user_score_log)) {
                    $goods_order_m->create();
                    $goods_order_m->pay_status = 1;
                    $goods_order_m->updated = time();
                    /*记录附属信息表START*/
                    $data_goods_order_detail['receipt_address_id'] = $address;
                    $data_goods_order_detail['updated'] = time();
                    /*记录附属信息表END*/
                    if ($goods_order_m->where('order_code=' . $order_code)->save() !== false && $goods_order_detail->where('order_id=' . $goods_order[0]['id'])->save($data_goods_order_detail) !== false) {
                        //商品删除
                        $goods_ids = explode(',', $goods_order[0]['goods_id']);
                        $wh['user_id'] = $uid;
                        $wh['goods_id'] = array('in', $goods_ids);
                        $shopping_num_array = M('shopping')->where($wh)->getField('goods_id,goods_num,goods_price', true);
                        /*订单附属商品表START*/
                        if ($goods_order[0]['goods_id'] != 0) {
                            if ($shopping_num_array) {
                                $where['id'] = array('in', $goods_ids);
                                $goods_ = M('goods')->where($where)->getField('id,thumb,title,unit_price');
                                foreach ($shopping_num_array as $k => $v) {
                                    foreach ($goods_ as $key => $value) {
                                        if ($v['goods_id'] == $key) {
                                            $shopping_num_array[$k]['goods'] = $value;
                                        }
                                    }
                                }
                            }
                            foreach ($shopping_num_array as $k => $v) {
                                $data_goods_order_shop['goods_id'] = $v['goods_id'];
                                $data_goods_order_shop['package_id'] = 0;
                                $data_goods_order_shop['type'] = 1;
                                $data_goods_order_shop['title'] = $v['goods']['title'];
                                $data_goods_order_shop['thumb'] = $v['goods']['thumb'];
                                $data_goods_order_shop['order_id'] = $order_id;
                                $data_goods_order_shop['num'] = $v['goods_num'];
                                $data_goods_order_shop['goods_price'] = $v['goods_price'];
                                $data_goods_order_shop['created'] = date('Y-d', time());
                                if (!$goods_order_shop->add($data_goods_order_shop)) {
                                    $model->rollback();
                                    $this->success('购买失败');exit;
                                }
                            }
                        }
                        /*订单附属商品表END*/
                        $where_num['goods_id'] = array('in', $goods_ids);
                        $where_num['user_id'] = $uid;
                        $goods_stock = M('goods_stock');
                        $goods_num_array = $goods_stock->where($where_num)->getField('id,goods_id,goods_num', true);
                        foreach ($goods_num_array as $k => $v) {
                            if ($v['goods_num'] < $shopping_num_array[$v['goods_id']]['goods_num']) {
                                $model->rollback();
                                $this->success('库存不足');exit;
                            }
                            $goods_stock_data['goods_num'] = $v['goods_num'] - $shopping_num_array[$v['goods_id']]['goods_num'];
                            $goods_stock->where('id=' . $v['id'])->save($goods_stock_data);
                        }
                        $list = M('shopping')->where($wh)->order('created')->limit('1')->delete();
                        //套餐删除
                        $package_ids = explode(',', $goods_order[0]['package_id']);
                        $wh_['user_id'] = $uid;
                        $wh_['package_id'] = array('in', $package_ids);
                        $shopping_num_array = M('shopping')->where($wh_)->getField('package_id,goods_num,goods_price', true);
                        /*订单附属商品表START*/
                        if ($goods_order[0]['package_id'] != 0) {
                            $where_['id'] = array('in', $package_ids);
                            $package_ = M('package')->where($where_)->getField('id, name, thumb, package_prices');
                            foreach ($package_ as $k => $v) {
                                $package_[$k]['title'] = $v['name'];
                                $package_[$k]['unit_price'] = $v['package_prices'];
                            }
                            foreach ($shopping_num_array as $k => $v) {
                                foreach ($package_ as $key => $value) {
                                    if ($v['package_id'] == $key) {
                                        $shopping_num_array[$k]['goods'] = $value;
                                    }
                                }
                            }
                            $data_goods_order_shop = array();
                            foreach ($shopping_num_array as $k => $v) {
                                $data_goods_order_shop['goods_id'] = 0;
                                $data_goods_order_shop['package_id'] = $v['package_id'];
                                $data_goods_order_shop['type'] = 2;
                                $data_goods_order_shop['title'] = $v['goods']['title'];
                                $data_goods_order_shop['thumb'] = $v['goods']['thumb'];
                                $data_goods_order_shop['order_id'] = $order_id;
                                $data_goods_order_shop['num'] = $v['goods_num'];
                                $data_goods_order_shop['goods_price'] = $v['goods_price'];
                                if (!$goods_order_shop->add($data_goods_order_shop)) {
                                    $model->rollback();
                                    $this->success('购买失败');exit;
                                }
                            }
                        }
                        /*订单附属商品表END*/
                        $where_package['package_id'] = array('in', $package_ids);
                        $where_package['status'] = 1;
                        $good_package_values = M('goods')->where($where_package)->getField('id,package_id,unit_price', true);
                        foreach ($good_package_values as $k => $v) {
                            if ($v['package_id'] == $shopping_num_array[$v['package_id']]['package_id']) {
                                $good_package_values[$k]['goods_num'] = $shopping_num_array[$v['package_id']]['goods_num'];
                            }
                        }
                        $good_package_ids = M('goods')->where($where_package)->getField('id', true);
                        $goods_stock = M('goods_stock');
                        $where_good_package_['goods_id'] = array('in', $good_package_ids);
                        //$where_good_package_['user_id'] = $uid;
                        $goods_package_num_array = $goods_stock->where($where_good_package_)->getField('id,goods_id,goods_num', true);
                        foreach ($goods_package_num_array as $k => $v) {
                            if ($v['goods_num'] < $good_package_values[$v['goods_id']]['goods_num']) {
                                $model->rollback();
                                $this->success('库存不足');exit;
                            }
                            $goods_stock_data['goods_num'] = $v['goods_num'] - $good_package_values[$v['goods_id']]['goods_num'];
                            $goods_stock_data['updated'] = time();
                            $goods_stock->where('id=' . $v['id'])->save($goods_stock_data);
                        }
                        $list_ = M('shopping')->where($wh_)->order('created')->limit('1')->delete();

                        if (($list !== false) || ($list_ !== false)) {
                            /*返利/奖励START*/
                            $type = parent::get_type();
                            $where_score_level_rule['id'] = array('in', array('1', '2', '6'));
                            $score_level_rule_find = $score_level_rule->where($where_score_level_rule)->getField('id,source_value,return_name,return_value,return_invite_user', true);
                            if ($type == 2) {
                                $member_score = $member->where('uid=' . $uid)->getField('uid,score,reward_times');
                                $member_score_key = parent::array_value($member_score);
                                if ($member_score_key[0]['reward_times'] == 0 && ($score_level_rule_find['2']['source_value'] <= $member_score_key[0]['score'])) {
                                    $result = self::RewardCalculation($uid, $score_level_rule_find['2']['source_value'], $score_level_rule_find['2']['return_invite_user']);//用户id，等级满足条件，返利率
                                } else if ($member_score_key[0]['reward_times'] == 1 && ($score_level_rule_find['6']['source_value'] <= $member_score_key[0]['score'])) {
                                    $zero_score = $member->where('status=1')->sum('score');//所有用户的消费流水总额
                                    $result = self::RewardCalculation($uid, $zero_score, $score_level_rule_find['6']['return_invite_user']);//用户id，等级满足条件，返利率
                                } else {
                                    //不作为
                                    $result = true;
                                }
                            }
                            $user_invite_user_value = $user_invite_user->where('user_id=' . $uid)->getField('invite_user_id');
                            if ($user_invite_user_value && $type == 3) {
                                $result = self::RewardCalculationOnline($uid, $user_invite_user_value, ($score_level_rule_find['1']['return_value']*$goods_order[0]['total']/100), $score_level_rule_find['1']['return_invite_user']);
                            } else if($type == 4) {
                                $result = true;
                            }
                            if ($result == true) {
                                $model->commit();
                                $this->success("商品购买成功", U('Order/receivinggoods'));
                            } else {
                                $model->rollback();
                                $this->success('购买失败', U('Index/index'));
                            }
                        } else {
                            $model->rollback();
                            $this->success('购买失败', U('Index/index'));
                        }
                    } else {
                        $model->rollback();
                        $this->success('购买失败', U('Index/index'));
                    }
                } else {
                    $model->rollback();
                    $this->success('购买失败', U('Index/index'));
                }
            } else {
                $model->rollback();
                $this->success('积分不足，请充值购买积分', U('Center/rechargeindex'));
            }
        } else {
            $shopping = M('shopping');
            $uid = parent::get_uid();
            $order_code = I('get.order_code');
            if($order_code) {
                $map['order_code'] = $order_code;
            }
            $map['user_id'] = $uid;
            $map['pay_status'] = 0;
            $map['status'] = 1;
            $goods_order = M('goods_order')->where($map)->order('id DESC')->field('id,order_code,goods_id,total,package_id')->limit(1)->select();
            if (!$goods_order) {
                $this->success('请从购物车选择有效商品', U('Shop/shopcart'));
            }
            $goods_order = parent::array_value($goods_order);
            $goods_ids = explode(',', $goods_order[0]['goods_id']);
            $where_goods_ids['goods_id'] = array('in', $goods_ids);
            $goods_exist = $shopping->where($where_goods_ids)->getField('id,goods_id,goods_num,goods_price', true);

            //print_r($goods_order);
            //$package_ids = parent::get_ids($goods_order, 'package_id');
            $package_ids = explode(',', $goods_order[0]['package_id']);
            $where_package_ids['package_id'] = array('in', $package_ids);
            $where_package_ids['status'] = 1;
            $package_exist = $shopping->where($where_package_ids)->getField('id,goods_id,goods_num,goods_price,package_id', true);
            if (!$goods_exist && !$package_exist) {
                $this->success('购物车没有商品', U('Index/index'));
            }
//            foreach ($package as $k => $v) {
//                $package[$k]['title'] = $v['name'];
//                $package[$k]['unit_price'] = $v['package_prices'];
//            }
            if ($goods_ids[0] == 0) {
                $goods_exist = array();
            } else {
                $where['id'] = array('in', $goods_ids);
                $goods_ = M('goods')->where($where)->getField('id,thumb,title,unit_price');
                foreach ($goods_exist as $k => $v) {
                    foreach ($goods_ as $key => $value) {
                        if ($v['goods_id'] == $key) {
                            $goods_exist[$k]['goods'] = $value;
                        }
                    }
                }
            }

            if ($package_ids[0] == 0) {
                $package_exist = array();
            } else {
                $where_['id'] = array('in', $package_ids);
                $package_ = M('package')->where($where_)->getField('id, name, thumb, package_prices');
                foreach ($package_ as $k => $v) {
                    $package_[$k]['title'] = $v['name'];
                    $package_[$k]['unit_price'] = $v['package_prices'];
                }
                foreach ($package_exist as $k => $v) {
                    foreach ($package_ as $key => $value) {
                        if ($v['package_id'] == $key) {
                            $package_exist[$k]['goods'] = $value;
                        }
                    }
                }
            }
            $this->assign('_goods_list', $goods_exist);
            $this->assign('_package_list', $package_exist);
            $this->assign('_goods_order', $goods_order);
            $this->assign('_list', $list);
            $this->meta_title = '支付订单';
            $this->display();
        }
    }

    //同类型的其他商品
    public static function get_id($id)
    {
        $class = M('shop_class')->where('status=1 && pid=' . $id)->getField('id, pid, name');
        return parent::array_value($class);
    }

    //父类的商品信息
    public static function get_pid($id)
    {
        $class = M('shop_class')->where('status=1 && id=' . $id)->getField('id, pid, name');
        return parent::array_value($class);
    }

    //奖励计算公式：创客奖励
    public static function RewardCalculation($user_id, $score, $return_invite_user)
    {
        $member = M('member');
        $user_score = M('user_score');
        $user_score_log = M('user_score_log');
        $user_score = self::array_value($user_score);
        $model = new \Think\Model();
        $score_value = $score * $return_invite_user / 100;
        $user_score->score = $user_score->score + $score_value;
        $user_score->updated = time();

        $member->reward_times = $member->reward_times + 1;
        $member->updated = time();

        $data_user_score_log['user_id'] = $user_id;
        $data_user_score_log['self_year_month_day'] = date('Y-m-d', time());
        $data_user_score_log['score'] = $score_value;
        $data_user_score_log['content'] = '创客订单累计奖励' . $score_value . '积分';
        $data_user_score_log['type'] = 5;
        $data_user_score_log['created'] = time();
        $data_user_score_log['status'] = 1;
        /*日志记录END*/
        if ($user_score->where('user_id=' . $user_id)->save() !== false && $member->where('uid=' . $user_id)->save() !== false && $user_score_log->add($data_user_score_log)) {
            return true;
        } else {
            return false;
        }
    }

    //奖励计算公式：VIP对上线的返利
    public static function RewardCalculationOnline($user_id, $invite_user, $score, $return_invite_user)
    {
        $member = M('member');
        $user_score = M('user_score');
        $user_score_log = M('user_score_log');
        //$user_score = self::array_value($user_score);
        //$model = new \Think\Model();
        $score_value = $score * $return_invite_user / 100;
        $find_score = $user_score->where('user_id=' . $invite_user)->getField('score');
        $user_score->score = $find_score + $score_value;
        $user_score->updated = time();
        $find_reward_times = $member->where('uid=' . $user_id)->getField('reward_times');
        $member->reward_times = $find_reward_times + 1;
        $member->updated = time();

        $data_user_score_log['user_id'] = $invite_user;
        $data_user_score_log['self_year_month_day'] = date('Y-m-d', time());
        $data_user_score_log['score'] = $score_value;
        $data_user_score_log['content'] = 'VIP订单返利上线,奖励' . $score_value . '积分';
        $data_user_score_log['type'] = 5;
        $data_user_score_log['created'] = time();
        $data_user_score_log['status'] = 1;
        /*日志记录END*/
        if ($user_score->where('user_id=' . $invite_user)->save() !== false && $member->where('uid=' . $user_id)->save() !== false && $user_score_log->add($data_user_score_log)) {
            return true;
        } else {
            return false;
        }
    }

}
