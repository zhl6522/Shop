<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Admin\Controller;

use User\Api\UserApi;

/**
 * 后台用户控制器
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
class OrderController extends AdminController
{

    /**
     * 订单管理首页
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */
    public function index()
    {
        $uid = parent::get_uid();
        $map['status'] = array('egt', 0);
        if (parent::get_type() != 1) {
            $map['user_id'] = $uid;
        }
        $nickname = I('nickname');
        if (!empty($nickname)) {
            if (is_numeric($nickname)) {
                $map['id|order_code'] = array(intval($nickname), array('like', '%' . $nickname . '%'), '_multi' => true);
            }
        }
        $list = $this->lists('goods_order', $map);
        int_to_string($list);
        pay_status_to_pay_status_name($list);
        goods_id_to_goods_id_name($list);
        $this->assign('_list', $list);
        $this->meta_title = '订单列表信息';
        $this->display();
    }

    /**
     * 待付款订单
     */
    public function pendingpayment()
    {
        $uid = parent::get_uid();
        $map['status'] = array('egt', 0);
        $map['pay_status'] = array('eq', 0);
        if (parent::get_type() != 1) {
            $map['user_id'] = $uid;
        }
        $nickname = I('nickname');
        if (!empty($nickname)) {
            if (is_numeric($nickname)) {
                $map['id|order_code'] = array(intval($nickname), array('like', '%' . $nickname . '%'), '_multi' => true);
            }
        }
        $list = $this->lists('goods_order', $map);
        int_to_string($list);
        pay_status_to_pay_status_name($list);
        goods_id_to_goods_id_name($list);
        $this->assign('_list', $list);
        $this->meta_title = '待付款订单';
        $this->display();
    }

    /**
     * 待发货订单
     */
    public function waitingfordelivery()
    {
        $uid = parent::get_uid();
        $map['status'] = array('egt', 0);
        $map['pay_status'] = array('eq', 1);
        $map['logistics_status'] = array('eq', 0);
        if (parent::get_type() != 1) {
            $map['user_id'] = $uid;
        }
        $nickname = I('nickname');
        if (!empty($nickname)) {
            if (is_numeric($nickname)) {
                $map['id|order_code'] = array(intval($nickname), array('like', '%' . $nickname . '%'), '_multi' => true);
            }
        }
        $list = $this->lists('goods_order', $map);
        int_to_string($list);
        logistics_status_to_logistics_status_name($list);
        goods_id_to_goods_id_name($list);
        $this->assign('_list', $list);
        $this->meta_title = '待发货订单';
        $this->display();
    }

    /**
     * 待收货订单
     */
    public function receivinggoods()
    {
        $goods_order = M('goods_order');
        $order_logistics = M('order_logistics');
        $logistics = M('logistics');
        $uid = parent::get_uid();
        $map['status'] = array('eq', 1);
        $map['pay_status'] = array('eq', 1);
        $map['logistics_status'] = array('ELT', 1);
        if (parent::get_type() != 1) {
            $map['user_id'] = $uid;
        }
        $nickname = I('nickname');
        if (!empty($nickname)) {
            if (is_numeric($nickname)) {
                $map['id|order_code'] = array(intval($nickname), array('like', '%' . $nickname . '%'), '_multi' => true);
            }
        }
        /*自动收货START*/
        $automatic_time = time() - 14 * 24 * 3600;
        $map['updated'] = array('ELT', $automatic_time);
        $automatic = M('goods_order')->where($map)->select();
        if ($automatic) {
            $model = new \Think\Model();
            $model->startTrans();
            $automatic_order_code = parent::get_ids($automatic, 'order_code');
            foreach ($automatic_order_code as $k => $v) {
                $automatic_order_code[$v] = $v;
                unset($automatic_order_code[$k]);
            }
            $where_['order_code'] = array('in', $automatic_order_code);
            $order_logistics_array = $order_logistics->where($where_)->getField('order_code,logistics_id, number', true);
            $order_logistics_ids = parent::get_ids($order_logistics_array, 'logistics_id');
            $where_order_logistics['id'] = array('in', $order_logistics_ids);
            $logistics_code = $logistics->where($where_order_logistics)->getField('id, code', true);
            foreach ($order_logistics_array as $k => $v) {
                header("Content-type: text/html; charset=utf-8");
                //$url = "http://test.ten.com/index.php?s=/Admin/LogisticsQuery/sign.html";
                $url = "http://zhl-wap.chaojue.org.cn/index.php?s=/Admin/LogisticsQuery/sign.html";
                $post_data = array('com' => $logistics_code[$v['logistics_id']], 'num' => $v['number']);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
                $output = curl_exec($ch);
                curl_close($ch);
                if ($output != 1) {
                    unset($automatic_order_code[$k]);
                }
            }
            if (count($automatic_order_code) != 0) {
                $where['logistics_status'] = 2;
                $where['updated'] = time();
                $where_order_logistics['status'] = 2;
                $where_order_logistics['updated'] = time();
                $automatic_order_code_string = implode(',', $automatic_order_code);
                if ($goods_order->where('order_code in (' . $automatic_order_code_string . ')')->save($where) !== false && $order_logistics->where('order_code in (' . $automatic_order_code_string . ')')->save($where_order_logistics) !== false) {
                    $model->commit();
                    //自动收货没问题
                } else {
                    $model->rollback();
                    $this->success('自动收货功能有问题');
                    exit;
                }
            }
        }
        /*自动收货END*/
        unset($map['updated']);
        $list = $this->lists('goods_order', $map);
        $list_ids = parent::get_ids($list, 'order_code');
        int_to_string($list);
        logistics_status_to_logistics_status_name($list);
        goods_id_to_goods_id_name($list);
        $where['order_code'] = array('in', $list_ids);
        $data = M('order_logistics')->where($where)->select();
        $logistics_ids = parent::get_ids($data, 'logistics_id');
        $wh['id'] = array('in', array_unique($logistics_ids));
        $logistics = M('logistics')->where($wh)->select();
        $logistics_new = parent::get_list($logistics, 'id');
        foreach ($data as $k => $v) {
            foreach ($logistics_new as $key => $value) {
                if ($v['logistics_id'] == $key) {
                    $data[$k]['name'] = $value['name'];
                    continue;
                }
            }
        }
        $data_new = parent::get_list($data, 'order_code');
        foreach ($list as $k => $v) {
            foreach ($data_new as $key => $value) {
                if ($v['order_code'] == $key) {
                    $list[$k]['number'] = $value['number'];
                    $list[$k]['name'] = $value['name'];
                    continue;
                }
            }
        }
        $this->assign('_list', $list);
        $this->meta_title = '待收货订单';
        $this->display();
    }

    /**
     * 已收货订单
     */
    public function receivedgoods()
    {
        $uid = parent::get_uid();
        $map['status'] = array('egt', 0);
        $map['pay_status'] = array('eq', 1);
        $map['logistics_status'] = array('eq', 2);
        if (parent::get_type() != 1) {
            $map['user_id'] = $uid;
        }
        $nickname = I('nickname');
        if (!empty($nickname)) {
            if (is_numeric($nickname)) {
                $map['id|order_code'] = array(intval($nickname), array('like', '%' . $nickname . '%'), '_multi' => true);
            }
        }
        $list = $this->lists('goods_order', $map);
        int_to_string($list);
        logistics_status_to_logistics_status_name($list);
        goods_id_to_goods_id_name($list);
        $this->assign('_list', $list);
        $this->meta_title = '已收货订单';
        $this->display();
    }

    /**
     * 添加物流
     */
    public function addlogistics()
    {
        if ($_POST) {
            $model = new \Think\Model();
            $model->startTrans();
            $data['order_code'] = I('post.order_code');
            $data['logistics_id'] = I('post.logistics');
            $data['number'] = I('post.number');
            $data['created'] = time();
            $data['status'] = 1;
            $order_logistics = M('order_logistics');
            $goods_order = M('goods_order');
            $goods_order->id = I('post.id');
            $goods_order->updated = time();
            $goods_order->logistics_status = 1;
            if ($goods_order->save() !== false && $order_logistics->add($data)) {
                $model->commit();
                $this->success('添加物流成功', U('Order/receivinggoods'));
            } else {
                $model->rollback();
                $this->success('添加物流失败');
                exit;
            }
        } else {
            $id = I('get.id');
            $map['id'] = $id;
            $data = M('goods_order')->where($map)->find();
            if (!$data) {
                $this->redirect('waitingfordelivery');
            }
            $logistics = M('logistics')->where('status=1')->select();
            $this->assign('data', $data);
            $this->assign('logistics', $logistics);
            $this->meta_title = '添加物流信息';
            $this->display();
        }
    }

    /**
     * 确定收货
     */
    public function editlogistics()
    {
        $model = new \Think\Model();
        $model->startTrans();
        $uid = parent::get_uid();
        $order_code = I('order_code', 0);
        if (empty($order_code)) {
            $this->success('请选择要操作的数据!');
            exit;
        }
        $res = M('goods_order');
        $res->updated = time();
        $res->logistics_status = 2;
        $order_logistics = M('order_logistics');
        $order_logistics->updated = time();
        $order_logistics->status = 2;
        if (parent::get_type() != 1) {
            $map = array('order_code' => $order_code, 'user_id' => $uid);
        } else {
            $map = array('order_code' => $order_code);
        }
        if ($res->where($map)->save() && $order_logistics->where($map)->save()) {
            $model->commit();
            $this->success('收货成功');
        } else {
            $model->rollback();
            $this->success('收货失败！');
            exit;
        }
    }

    /**
     * 订单详情页
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */
    public function show()
    {
        $address = M("address");
        $district = M('district');
        $goods_order = M('goods_order');
        $goods_order_detail = M('goods_order_detail');
        $goods_order_shop = M('goods_order_shop');
        $order_code = I('get.order_code');
        $uid = parent::get_uid();
        $map['status'] = array('egt', 0);
        if (parent::get_type() != 1) {
            $map['user_id'] = $uid;
        }
        $map['order_code'] = $order_code;
        $data = $goods_order->where($map)->find();
        if (!$data) {
            $this->redirect('index');
        }
        $data_detail = $goods_order_detail->where('order_id =' . $data['id'])->find();
        pay_status_one_to_pay_status_name_one($data);
        if ($data['pay_status'] == 1) {
            pay_goods_id_to_goods_id_array($data);
        } else {
            no_pay_goods_id_to_goods_id_array($data);
        }
        $data_detail['detail'] = $address->find($data_detail['receipt_address_id']);
        $data_detail['detail']['province_name'] = $district->where("id=" . $data_detail['detail']['province_id'])->getField("name");
        $data_detail['detail']['city_name'] = $district->where("id=" . $data_detail['detail']['city_id'])->getField("name");
        $data_detail['detail']['district_name'] = $district->where("id=" . $data_detail['detail']['district_id'])->getField("name");
        $this->assign('data', $data);
        $this->assign('data_detail', $data_detail);
        $this->meta_title = '订单列表信息';
        $this->display();
    }

    /**
     * 删除订单行为
     * @author zhl <ahfuzl@126.com>
     */
    public function del()
    {
        $uid = parent::get_uid();
        $id = array_unique((array)I('id', 0));
        if (empty($id)) {
            $this->success('请选择要操作的数据!');
            exit;
        }
        $res = M('goods_order');
        $res->updated = time();
        $res->status = -1;
        $map = array('id' => array('in', $id), 'user_id' => $uid);
        if ($insertid = $res->where($map)->save()) {
            $this->success('删除成功');
        } else {
            $this->success('删除失败！');
            exit;
        }
    }

    /**
     * 修改昵称初始化
     * @author huajie <banhuajie@163.com>
     */
    public function updateNickname()
    {
        $nickname = M('Member')->getFieldByUid(UID, 'nickname');
        $this->assign('nickname', $nickname);
        $this->meta_title = '修改昵称';
        $this->display();
    }

    /**
     * 修改昵称提交
     * @author huajie <banhuajie@163.com>
     */
    public function submitNickname()
    {
        //获取参数
        $nickname = I('post.nickname');
        $password = I('post.password');
        if (empty($nickname)) {
            $this->success('请输入昵称');
            exit;
        }
        if (empty($password)) {
            $this->success('请输入密码');
            exit;
        }

        //密码验证
        $User = new UserApi();
        $uid = $User->login(UID, $password, 4);
        if($uid == -2) {
            $this->success('密码不正确');
            exit;
        }

        $Member = D('Member');
        $data = $Member->create(array('nickname' => $nickname));
        if (!$data) {
            $this->success($Member->getError());exit;
        }

        $res = $Member->where(array('uid' => $uid))->save($data);

        if ($res) {
            $user = session('user_auth');
            $user['username'] = $data['nickname'];
            session('user_auth', $user);
            session('user_auth_sign', data_auth_sign($user));
            $this->success('修改昵称成功！');
        } else {
            $this->success('修改昵称失败！');
            exit;
        }
    }

    /**
     * 修改密码初始化
     * @author huajie <banhuajie@163.com>
     */
    public function updatePassword()
    {
        $this->meta_title = '修改密码';
        $this->display();
    }

    /**
     * 修改密码提交
     * @author huajie <banhuajie@163.com>
     */
    public function submitPassword()
    {
        //获取参数
        $password = I('post.old');
        empty($password) && $this->error('请输入原密码');
        $data['password'] = I('post.password');
        empty($data['password']) && $this->error('请输入新密码');
        $repassword = I('post.repassword');
        empty($repassword) && $this->error('请输入确认密码');

        if ($data['password'] !== $repassword) {
            $this->error('您输入的新密码与确认密码不一致');
        }

        $Api = new UserApi();
        $res = $Api->updateInfo(UID, $password, $data);
        if ($res['status']) {
            $this->success('修改密码成功！');
        } else {
            $this->error($res['info']);
        }
    }

    /**
     * 用户行为列表
     * @author huajie <banhuajie@163.com>
     */
    public function action()
    {
        //获取列表数据
        $Action = M('Action')->where(array('status' => array('gt', -1)));
        $list = $this->lists($Action);
        int_to_string($list);
        // 记录当前列表页的cookie
        Cookie('__forward__', $_SERVER['REQUEST_URI']);

        $this->assign('_list', $list);
        $this->meta_title = '用户行为';
        $this->display();
    }

    /**
     * 新增行为
     * @author huajie <banhuajie@163.com>
     */
    public function addAction()
    {
        $this->meta_title = '新增行为';
        $this->assign('data', null);
        $this->display('editaction');
    }

    /**
     * 编辑行为
     * @author huajie <banhuajie@163.com>
     */
    public function editAction()
    {
        $id = I('get.id');
        empty($id) && $this->error('参数不能为空！');
        $data = M('Action')->field(true)->find($id);

        $this->assign('data', $data);
        $this->meta_title = '编辑行为';
        $this->display();
    }

    /**
     * 更新行为
     * @author huajie <banhuajie@163.com>
     */
    public function saveAction()
    {
        $res = D('Action')->update();
        if (!$res) {
            $this->error(D('Action')->getError());
        } else {
            $this->success($res['id'] ? '更新成功！' : '新增成功！', Cookie('__forward__'));
        }
    }

    /**
     * 会员状态修改
     * @author 朱亚杰 <zhuyajie@topthink.net>
     */
    public function changeStatus($method = null)
    {
        $id = array_unique((array)I('id', 0));
        if (in_array(C('USER_ADMINISTRATOR'), $id)) {
            $this->error("不允许对超级管理员执行该操作!");
        }
        $id = is_array($id) ? implode(',', $id) : $id;
        if (empty($id)) {
            $this->error('请选择要操作的数据!');
        }
        $map['uid'] = array('in', $id);
        switch (strtolower($method)) {
            case 'forbiduser':
                $this->forbid('Member', $map);
                break;
            case 'resumeuser':
                $this->resume('Member', $map);
                break;
            case 'deleteuser':
                $this->delete('Member', $map);
                break;
            default:
                $this->error('参数非法');
        }
    }

    public function add($username = '', $password = '', $repassword = '', $email = '')
    {
        if (IS_POST) {
            /* 检测密码 */
            if ($password != $repassword) {
                $this->error('密码和重复密码不一致！');
            }

            /* 调用注册接口注册用户 */
            $User = new UserApi;
            $uid = $User->register($username, $password, $email);
            if (0 < $uid) { //注册成功
                $user = array('uid' => $uid, 'nickname' => $username, 'status' => 1);
                if (!M('Member')->add($user)) {
                    $this->error('用户添加失败！');
                } else {
                    $this->success('用户添加成功！', U('index'));
                }
            } else { //注册失败，显示错误信息
                $this->error($this->showRegError($uid));
            }
        } else {
            $this->meta_title = '新增用户';
            $this->display();
        }
    }

    /**
     * 获取用户注册错误信息
     * @param  integer $code 错误编码
     * @return string        错误信息
     */
    private function showRegError($code = 0)
    {
        switch ($code) {
            case -1:
                $error = '用户名长度必须在16个字符以内！';
                break;
            case -2:
                $error = '用户名被禁止注册！';
                break;
            case -3:
                $error = '用户名被占用！';
                break;
            case -4:
                $error = '密码长度必须在6-30个字符之间！';
                break;
            case -5:
                $error = '邮箱格式不正确！';
                break;
            case -6:
                $error = '邮箱长度必须在1-32个字符之间！';
                break;
            case -7:
                $error = '邮箱被禁止注册！';
                break;
            case -8:
                $error = '邮箱被占用！';
                break;
            case -9:
                $error = '手机格式不正确！';
                break;
            case -10:
                $error = '手机被禁止注册！';
                break;
            case -11:
                $error = '手机号被占用！';
                break;
            default:
                $error = '未知错误';
        }
        return $error;
    }

    public  function status(){
        $logitic=  A('LogisticsQuery');
        $date=$logitic->status();
        $this->assign("date",$date);
        $this->display();
    }
}
