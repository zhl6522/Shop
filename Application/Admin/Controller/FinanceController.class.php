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
class FinanceController extends AdminController {

    /**
     * 等级返利
     * @author zhl <ahfuzl@126.com>
     */
    public function index(){
        $uid = parent::get_uid();
        $type = parent::get_type();
        if($type ==2) {
            $this->redirect('Finance/total');
        }
        if($type ==1) {
            $this->redirect('Finance/rebate');
        }
        $score_level_rule = M('score_level_rule');
        $goods_order = M('goods_order');
        $score_level_rule_data = $score_level_rule->where('type='.$type)->order('id asc')->limit(1)->find();
        $map['user_id'] = $uid;
        $map['status']  =   array('eq',1);
        $map['pay_status']  =   array('eq',1);
        $map['first']  =   array('eq',0);
        //$register = $goods_order->where($map)->order('id asc')->limit(1)->find();
        $start = I('get.start');
        $end = I('get.end');
        if($start && $end) {
            $start_time = $start.'-01 00:00:00';
            $end_time_strtotime = strtotime("+1months",strtotime($end));
            date('Y-m-d H:i:s', $end_time_strtotime);
            $start_time_strtotime = strtotime($start_time);
            $end_time_strtotime = $end_time_strtotime;
        } else {
            $start = date('Y-m', time());
            $end = date('Y-m', time());
            $start_time_strtotime = strtotime(date('Y-m', time()));
            $end_time_strtotime = strtotime("+1months", $start_time_strtotime);
        }
        $map['created'] = array('between',array($start_time_strtotime, $end_time_strtotime));

        $nickname = I('get.nickname');
        if (!empty($nickname)) {
            if (is_numeric($nickname)) {
                $map['order_code']  = array(array('like', "%$nickname%"));
                //$map['_query'] = 'order_code like %'.$nickname.'%&order_code!='.$register['order_code'].'&_logic=or';
            }
        }
        $sum = $goods_order->where($map)->sum('total');
        //$sum = $goods_order->where($map)->sum('binary total');
        $real_sum = $goods_order->where($map)->sum('rebate_total');
        $rebate = $sum - $real_sum;
        $list   = $this->lists('goods_order', $map, '', '', 'id,user_id,order_code,user_score_level_rule_id,total,rebate_total,pay_status,created,status');
        //package_id_to_package_id_name($list);
        $user_ids = array_unique(parent::get_ids($list, 'user_id'));
        $user_datail = user_id_to_nickname($user_ids);
//        $user_score_level_rule_ids = array_unique(parent::get_ids($list, 'user_score_level_rule_id'));
//        $data = user_score_level_rule_id_to_user_score_level_rule_id_name($user_score_level_rule_ids);
        foreach($list as $k => $v) {
            //$order_price = number_format($v['total']/$score_level_rule_data['return_value']*100, 2, '.', '');
            //$list[$k]['order_price'] = floatval($order_price);
            //$list[$k]['brbate_price'] = $order_price-$v['total'];
            $list[$k]['nickname'] = $user_datail[$v['user_id']];
            $list[$k]['return_name'] = $score_level_rule_data['return_name'];
            $list[$k]['return_value'] = $score_level_rule_data['return_value']/10;
//            foreach($data as $key => $value) {
//                if($v['user_score_level_rule_id'] == $key) {
//                    $list[$k]['user_detail'] = $value;
//                }
//            }
        }
        $score = M('user_score')->where('user_id='.$uid)->getField('score');
        $this->assign('start', $start);
        $this->assign('end', $end);
        $this->assign('score', $score);
        $this->assign('sum', $sum);
        $this->assign('real_sum', $real_sum);
        $this->assign('rebate', $rebate);
        $this->assign('_list', $list);
        $this->meta_title = '二次返利列表';
        $this->display();
    }

    public  function total(){
        $uid = parent::get_uid();
        $type = parent::get_type();
        if($type==2  ){
            $name=M('member')->where('uid='.$uid)->getField("nickname");
            $grand=M('member')->where('uid='.$uid)->getField("score");
            $score=M("user_score")->where('user_id='.$uid)->field("score")->find();;
            $score=current($score);
            $where["pay_status"]=1;
            $where["status"]=1;
            $where["first"]=0;
            $where["return_score"]=array('neq',0);
            $where["user_id"]=$uid;
           $total= M('goods_order')->where($where)->sum('return_score');
            $list=$this->lists('goods_order',$where);
         if($list){
             foreach($list as $k=>$v){
                 $list[$k]["name"]= $name;
             }
         }
            $this->assign('list',$list);// 赋值数据集
            $this->assign('total',$grand);// 累计消费
            $this->assign('score',$score);// 当前总积分
            $this->assign('rebate',$total);// 返利
            $this->display(); // 输出模板
        }else{

            $this->redirect('Finance/rebate');
        }



    }
    /**
     * 下线返利
     * @author zhl <ahfuzl@126.com>
     */
    public function offline(){
        $nickname = I('nickname');
        if (!empty($nickname)) {
            if (is_numeric($nickname)) {
                $map['id|order_code'] = array(intval($nickname), array('like', '%' . $nickname . '%'), '_multi' => true);
            }
        }
        $uid = parent::get_uid();
        $score_level_rule = M('score_level_rule');
        $goods_order = M('goods_order');
        $user_ids = M('user_invite_user')->where('invite_user_id='.$uid)->getField('user_id', true);
        $map['user_id'] = array('in', $user_ids);
        $score_level_rule_data = $score_level_rule->where('id=2')->find();
        $map['status']  =   array('eq',1);
        $map['is_rebate_total']  =   array('eq',1);
        $map['pay_status']  =   array('eq',1);
        //$register = $goods_order->where($map)->order('id asc')->limit(1)->find();
        $start = I('get.start');
        $end = I('get.end');
        if($start && $end) {
            $start_time = $start.'-01 00:00:00';
            $end_time_strtotime = strtotime("+1months",strtotime($end));
            $start_time_strtotime = strtotime($start_time);
            $end_time_strtotime = $end_time_strtotime;
        } else {
            $start = date('Y-m', time());
            $end = date('Y-m', time());
            $start_time_strtotime = strtotime(date('Y-m', time()));
            $end_time_strtotime = strtotime("+1months", $start_time_strtotime);
        }
        $map['created'] = array('between',array($start_time_strtotime, $end_time_strtotime));
        //$map['order_code'] = array('neq', $register['order_code']);
        $sum = $goods_order->where($map)->sum('binary total');
        $real_sum = $goods_order->where($map)->sum('rebate_total');
        $rebate = $real_sum*$score_level_rule_data['return_invite_user']/100;
        //$rebate = number_format($sum/$score_level_rule_data['return_value']*$score_level_rule_data['return_invite_user'],2);

        $nickname = I('get.nickname');
        if (!empty($nickname)) {
            if (is_numeric($nickname)) {
                $map['order_code']  = array('like', "%$nickname%");
                //$map['_query'] = 'order_code like %'.$nickname.'%&order_code!='.$register['order_code'].'&_logic=or';
            }
        }
        $list   = $this->lists('goods_order', $map, '', '', 'id,user_id,order_code,user_score_level_rule_id,total,rebate_total,pay_status,created,updated,status');
        package_id_to_package_id_name($list);
        $user_ids = array_unique(parent::get_ids($list, 'user_id'));
        $user_datail = user_id_to_nickname($user_ids);
        $user_score_level_rule_ids = array_unique(parent::get_ids($list, 'user_score_level_rule_id'));
        $data = user_score_level_rule_id_to_user_score_level_rule_id_name($user_score_level_rule_ids);
        foreach($list as $k => $v) {
            $list[$k]['nickname'] = $user_datail[$v['user_id']];
            $list[$k]['order_price'] = $v['total'];
            $list[$k]['return_invite_user'] = $score_level_rule_data['return_invite_user'];
            $list[$k]['return_total'] = $v['rebate_total'] * $score_level_rule_data['return_invite_user']/100;
//            foreach($data as $key => $value) {
//                if($v['user_score_level_rule_id'] == $key) {
//                    $list[$k]['user_detail'] = $value;
//                }
//            }
        }
//        foreach($list as $k => $v) {
//            $list[$k]['return_total'] = number_format($v['total'] * $v['user_detail']['return_invite_user']/100,2);
//        }
        $this->assign('start', $start);
        $this->assign('end', $end);
        $this->assign('rebate', $rebate);
        $this->assign('_list', $list);
        $this->meta_title = '下线返利信息';
        $this->display();
    }

    /**
     * 返利比率设置
     * @author zhl <ahfuzl@126.com>
     */
    public function rebate() {
        $where['status'] = 1;
        $list = M('score_level_rule')->where($where)->getField('id,source_value,return_name,return_value,return_invite_user', true);
        $this->assign('_list', $list);
        $this->meta_title = '返利比率设置';
        $this->display();
    }

    /**
     * 修改返利比率
     * @author zhl <ahfuzl@126.com>
     */
    public function editRebate()
    {
        if ($_POST) {
            $id = I('post.id');
            $score_level_rule = D('score_level_rule');
            if ($score_level_rule->create()) {
                $score_level_rule->source_value = I('post.source_value');
                $score_level_rule->return_value = I('post.return_value');
                $score_level_rule->return_invite_user = I('post.return_invite_user');
                $score_level_rule->updated = time();
                if ($insertid = $score_level_rule->save()) {
                    $this->success('更新成功！', U('Finance/rebate'));
                } else {
                    $this->error('更新失败');
                }
            } else {
                $this->error($score_level_rule->getError());
            }
        } else {
            $id = I('get.id');
            $where['id'] = intval($id);
            $list = M('score_level_rule')->where($where)->find();
            $this->assign('data', $list);
            $this->meta_title = '修改返利比率';
            $this->display();
        }
    }

    /**
     * 修改昵称初始化
     * @author huajie <banhuajie@163.com>
     */
    public function updateNickname(){
        $nickname = M('Member')->getFieldByUid(UID, 'nickname');
        $this->assign('nickname', $nickname);
        $this->meta_title = '修改昵称';
        $this->display();
    }

    /**
     * 修改昵称提交
     * @author huajie <banhuajie@163.com>
     */
    public function submitNickname(){
        //获取参数
        $nickname = I('post.nickname');
        $password = I('post.password');
        empty($nickname) && $this->error('请输入昵称');
        empty($password) && $this->error('请输入密码');

        //密码验证
        $User   =   new UserApi();
        $uid    =   $User->login(UID, $password, 4);
        ($uid == -2) && $this->error('密码不正确');

        $Member =   D('Member');
        $data   =   $Member->create(array('nickname'=>$nickname));
        if(!$data){
            $this->error($Member->getError());
        }

        $res = $Member->where(array('uid'=>$uid))->save($data);

        if($res){
            $user               =   session('user_auth');
            $user['username']   =   $data['nickname'];
            session('user_auth', $user);
            session('user_auth_sign', data_auth_sign($user));
            $this->success('修改昵称成功！');
        }else{
            $this->error('修改昵称失败！');
        }
    }

    /**
     * 修改密码初始化
     * @author huajie <banhuajie@163.com>
     */
    public function updatePassword(){
        $this->meta_title = '修改密码';
        $this->display();
    }

    /**
     * 修改密码提交
     * @author huajie <banhuajie@163.com>
     */
    public function submitPassword(){
        //获取参数
        $password   =   I('post.old');
        empty($password) && $this->error('请输入原密码');
        $data['password'] = I('post.password');
        empty($data['password']) && $this->error('请输入新密码');
        $repassword = I('post.repassword');
        empty($repassword) && $this->error('请输入确认密码');

        if($data['password'] !== $repassword){
            $this->error('您输入的新密码与确认密码不一致');
        }

        $Api    =   new UserApi();
        $res    =   $Api->updateInfo(UID, $password, $data);
        if($res['status']){
            $this->success('修改密码成功！');
        }else{
            $this->error($res['info']);
        }
    }

    /**
     * 用户行为列表
     * @author huajie <banhuajie@163.com>
     */
    public function action(){
        //获取列表数据
        $Action =   M('Action')->where(array('status'=>array('gt',-1)));
        $list   =   $this->lists($Action);
        int_to_string($list);
        // 记录当前列表页的cookie
        Cookie('__forward__',$_SERVER['REQUEST_URI']);

        $this->assign('_list', $list);
        $this->meta_title = '用户行为';
        $this->display();
    }

    /**
     * 新增行为
     * @author huajie <banhuajie@163.com>
     */
    public function addAction(){
        $this->meta_title = '新增行为';
        $this->assign('data',null);
        $this->display('editaction');
    }

    /**
     * 编辑行为
     * @author huajie <banhuajie@163.com>
     */
    public function editAction(){
        $id = I('get.id');
        empty($id) && $this->error('参数不能为空！');
        $data = M('Action')->field(true)->find($id);

        $this->assign('data',$data);
        $this->meta_title = '编辑行为';
        $this->display();
    }

    /**
     * 更新行为
     * @author huajie <banhuajie@163.com>
     */
    public function saveAction(){
        $res = D('Action')->update();
        if(!$res){
            $this->error(D('Action')->getError());
        }else{
            $this->success($res['id']?'更新成功！':'新增成功！', Cookie('__forward__'));
        }
    }

    /**
     * 会员状态修改
     * @author 朱亚杰 <zhuyajie@topthink.net>
     */
    public function changeStatus($method=null){
        $id = array_unique((array)I('id',0));
        if( in_array(C('USER_ADMINISTRATOR'), $id)){
            $this->error("不允许对超级管理员执行该操作!");
        }
        $id = is_array($id) ? implode(',',$id) : $id;
        if ( empty($id) ) {
            $this->error('请选择要操作的数据!');
        }
        $map['uid'] =   array('in',$id);
        switch ( strtolower($method) ){
            case 'forbiduser':
                $this->forbid('Member', $map );
                break;
            case 'resumeuser':
                $this->resume('Member', $map );
                break;
            case 'deleteuser':
                $this->delete('Member', $map );
                break;
            default:
                $this->error('参数非法');
        }
    }

    public function add($username = '', $password = '', $repassword = '', $email = ''){
        if(IS_POST){
            /* 检测密码 */
            if($password != $repassword){
                $this->error('密码和重复密码不一致！');
            }

            /* 调用注册接口注册用户 */
            $User   =   new UserApi;
            $uid    =   $User->register($username, $password, $email);
            if(0 < $uid){ //注册成功
                $user = array('uid' => $uid, 'nickname' => $username, 'status' => 1);
                if(!M('Member')->add($user)){
                    $this->error('用户添加失败！');
                } else {
                    $this->success('用户添加成功！',U('index'));
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
    private function showRegError($code = 0){
        switch ($code) {
            case -1:  $error = '用户名长度必须在16个字符以内！'; break;
            case -2:  $error = '用户名被禁止注册！'; break;
            case -3:  $error = '用户名被占用！'; break;
            case -4:  $error = '密码长度必须在6-30个字符之间！'; break;
            case -5:  $error = '邮箱格式不正确！'; break;
            case -6:  $error = '邮箱长度必须在1-32个字符之间！'; break;
            case -7:  $error = '邮箱被禁止注册！'; break;
            case -8:  $error = '邮箱被占用！'; break;
            case -9:  $error = '手机格式不正确！'; break;
            case -10: $error = '手机被禁止注册！'; break;
            case -11: $error = '手机号被占用！'; break;
            default:  $error = '未知错误';
        }
        return $error;
    }

}
