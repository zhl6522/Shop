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
class CenterController extends AdminController
{

    /**
     * 后台首页
     * @author zhl <ahfuzl@126.com>
     */
//    public function index()
//    {
//        $menus = M('shop_class')->where('status=1 && pid=0')->getField('id,type,name,level');
//        $menu_ids = parent::get_ids($menus, 'id');
//        $wheres['pid'] = array('in', $menu_ids);
//        $wheres['level'] = 2;
//        $wheres['status'] = 1;
//        $shop_class = M('shop_class')->where($wheres)->order('id DESC')->getField('id,type,name,level,pid');
//        foreach ($menus as $k => $v) {
//            foreach ($shop_class as $key => $value) {
//                if ($value['pid'] == $k) {
//                    $menus[$k]['shop'][$key] = $value;
//                }
//            }
//        }
//        shop_to_is_last($menus);
//        $map['status'] = array('eq', 1);
//        $map['top'] = array('eq', 1);
//        $list = M('goods')->where($map)->order('id desc')->limit(10)->getField('id,title,thumb,unit_price');
//        $where['status'] = array('eq', 1);
//        $package = M('package')->where('package_top = 1')->order('id DESC')->limit(3)->getField('id,name,package_price,package_prices');
//        $ids = parent::get_ids($package, 'id');
//        $where['package_id'] = array('in', $ids);
//        $package_goods = M('goods')->where($where)->order('id DESC')->getField('id,title,thumb,unit_price,package_id');
//        foreach ($package as $k => $v) {
//            foreach ($package_goods as $key => $value) {
//                if ($value['package_id'] == $k) {
//                    $package[$k]['goods'][$key] = $value;
//                }
//            }
//        }
//        is_last_to_is_last($package);
//        $this->assign('_menus', $menus);
//        $this->assign('_list', $list);
//        $this->assign('_package', $package);
//        $this->meta_title = '管理首页';
//        $this->display();
//    }

    public function index(){
        $uid = $_SESSION['onethink_admin']['user_auth']['uid'];
        if(IS_POST){
            $model = M("member");
            if($model->create()){
                $where['uid'] = array("eq",$uid);
                if($model->where($where)->save()){
                    $this->success("操作成功");
                }else{
                    echo M()->_sql();exit;
                    $this->error("系统繁忙");
                }
            }else{
                $this->error("非法数据");
            }
        }else{
            $data = M("member")->where("uid = ".$uid)->find();
            $data['username'] = M("ucenter_member")->where("id=".$uid)->getField("username");
            $where['uesr_id'] = array("eq",$uid);
            $where['type'] = array("eq",1);
            $total_num = M("user_score_log")->where($where)->sum("score");
            if($total_num>16660 && $data['type'] ==3){
                $this->level_type = 2;
            }
            if($total_num<16660 && $data['type'] ==3){
                $this->level_type = 1;
            }

            $this->list = M("district")->where("upid=0")->select();
            $data['city_name'] = M("district")->where("id=".$data['city_id'])->getField("name");
            $data['district_name'] = M("district")->where("id=".$data['district_id'])->getField("name");

            $data['totalscore'] = M("user_score")->where("user_id=".$uid)->getField("score");
            $this->data = $data;
            $this->display();
        }

    }

    public function shopping()
    {
        $this->redirect('Shop/shopcart');
    }

    public function recharge() {
        $this->meta_title = '充值';
        $this->display();
    }

    //支付接口
    public function pay(){
        $score = I('post.score')*100;
        $success_url = "http://".$_SERVER['SERVER_NAME']."/Admin/Center/successPay/score/".$score;
        $cancel_url = $_SERVER['SERVER_NAME']."/Admin/Center/cancelPay";
        $order = time().rand(0000,9999);
        require_once('pingpp/init.php');
        \Pingpp\Pingpp::setApiKey('**********');
        \Pingpp\Pingpp::setPrivateKeyPath('/data/develop/php/zhl.code/rsa_private_key.pem');
        $json =  \Pingpp\Charge::create(array('order_no'  => $order,
                'amount'    => $score,//订单总金额, 人民币单位：分（如订单总金额为 1 元，此处请填 100）
                'app'       => array('id' => 'app_48qPK8er9Gq1zPWT'),
                'channel'   => 'alipay_pc_direct',
                'currency'  => 'cny',
                'client_ip' => '103.235.**.**',
                'subject'   => '会员充值',
                'body'      => '会员充值',
                'extra'=>array(
                    'success_url'=>$success_url,
//                    'enable_anti_phishing_key '=>'',
//                    'exter_invoke_ip '=>'103.235.**.**',
                ))
        );

        echo $json;
    }

    //支付页面
    public function payHtml(){
        $this->display("pay");
    }
    //更新支付接品
    public function successPay(){
        $uid = parent::get_uid();
        $user_score_m = M('user_score');
        $user_score_log = M('user_score_log');
        $user_score = $user_score_m->where('user_id='.$uid)->getField('id, user_id, score');
        $score = I('get.score')/100;
        $user_score = parent::array_value($user_score);
        if(!$user_score) {
//            $model = new \Think\Model();
//            $model->startTrans();
            $data['user_id'] = $uid;
            $data['score'] = $score;
            $data['created'] = time();
            $data['status'] = 1;
//            if($user_score_m->add($data)) {
//                //echo M()->_sql();
//                $this->error('充值失败');
//            }
        } else {
            $data['score'] = $user_score[0]['score'] + $score;
            $data['updated'] = time();
            $user_score_m->where('id='.$user_score[0]['id'])->save($data);
        }
        $data_user_score_log['user_id'] = $uid;
        $data_user_score_log['self_year_month_day'] = date('Y-m-d', time());
        $data_user_score_log['score'] = $score;
        $data_user_score_log['content'] = '用户充值'.$score.'元';
        $data_user_score_log['type'] = 1;
        $data_user_score_log['created'] = time();
        $data_user_score_log['status'] = 1;
        $user_score_log->add($data_user_score_log);
        $this->redirect('Order/pendingpayment');

//        $id = $_SESSION['onethink_admin']['user_auth']['uid'];
//        $data['updated'] = time(0);
//        $data['pay_status'] = 1;
//        M("member")->where("uid=".$id)->save($data);
    }
    //更新支付接品
    public function canclePay(){
        echo  "支付取消";
    }


    //收货地址列表
    public function addressIndex(){
        $uid = $_SESSION['onethink_admin']['user_auth']['uid'];
        $list = M("address")->where("uid = ".$uid)->select();

        $res = array();
        foreach($list as $v){
            if(!in_array($v['province_id'],$res)){
                array_push($res,$v['province_id']);
            }
            if(!in_array($v['city_id'],$res)){
                array_push($res,$v['city_id']);
            }
            if(!in_array($v['district_id'],$res)){
                array_push($res,$v['district_id']);
            }
        }
        $where['id'] = array("in",$res);
        $list_address = M("district")->field("name,id")->where($where)->select();

        foreach($list_address as $vales){
            $arr[$vales['id']] = $vales['name'];
        }

        foreach($list as &$vs){
            $vs['province_name'] = $arr[$vs['province_id']];
            $vs['city_name'] = $arr[$vs['city_id']];
            $vs['district_name'] = $arr[$vs['district_id']];
            if($vs['status']==1){
                $vs['status_text'] = "默认地址";
            }
        }
        $this->_list = $list;
        $this->display("addressIndex");
    }

    //添加收货地址
    public function addressAdd(){
        if(IS_POST){
            $model = M("address");
            $uid = $_SESSION['onethink_admin']['user_auth']['uid'];
            if($model->create()){ //注册成功
                $model->uid = $uid;
                $model->created = time();
                $model->status = I("status")?I("status"):0;
                if(I("status")==1){
                    M("address")->where("uid=".$uid)->setField('status',0);
                }
                if(!$model->add()){
                    $this->error('添加失败！');
                } else {
                    $this->success('添加成功！',U('addressIndex'));
                }
            } else { //注册失败，显示错误信息
                $this->error("添加失败");
            }
        } else {
            $this->list = M("district")->where("upid=0")->select();
            $this->display("addressAdd");
        }
    }

    //删除收货地址信息
    public function addressDelete(){
        $uid = $_SESSION['onethink_admin']['user_auth']['uid'];
        $id = I("id");
        $where['uid'] = array("eq",$uid);
        $where['id'] = array("eq",$id);
        if(M("address")->where($where)->delete()){
            $this->success("删除成功");
        }else{
            $this->error("删除失败");
        }
    }

    //修改收货地址
    public function addressUpdate(){
        $model = M("address");
        if(IS_POST){
            $uid = $_SESSION['onethink_admin']['user_auth']['uid'];
            if($model->create()){ //注册成功
                $model->updated = time();
                $model->status = I("status")?I("status"):0;
                if(I("status")==1){
                    M("address")->where("uid=".$uid)->setField('status',0);
                }
                if(!$model->save()){
                    $this->error('修改失败！');
                } else {
                    $this->success('修改成功！',U('addressIndex'));
                }
            } else { //注册失败，显示错误信息
                $this->error("修改失败");
            }
        } else {
            $this->list = M("district")->where("upid=0")->select();
            $data = $model->find(I("id"));
            $data['city_name'] = M("district")->where("id=".$data['city_id'])->getField("name");
            $data['district_name'] = M("district")->where("id=".$data['district_id'])->getField("name");
            $this->data= $data;
            $this->display("addressUpdate");
        }
    }

    //用户充值记录列表
    public function rechargeindex(){
        $type = $_SESSION['onethink_admin']['user_info']['type'];
        $this->type = $type;
        if($type !=1){
            $map['user_id'] = $_SESSION['onethink_admin']['user_auth']['uid'];
        }
        $map['type'] =array('eq',1);
        $list   = $this->lists('user_score_log', $map);
        foreach($list as &$v){
            $v['username'] = M("ucenter_member")->where("id=".$v['user_id'])->getField("username");
        }
        int_to_string($list);
        $this->assign('_list', $list);
        $this->meta_title = '充值信息';
        $this->display();
    }

    //用户提现申请列表
    public function withdrawindex(){
        $type = $_SESSION['onethink_admin']['user_info']['type'];
        $this->type = $type;
        if($type !=1){
            $map['user_id'] = $_SESSION['onethink_admin']['user_auth']['uid'];
        }
        $list   = $this->lists('withdraw', $map);
        int_to_string($list);
        $this->assign('_list', $list);
        $this->meta_title = '申请提现信息';
        $this->display();
    }
    //提现申请
    public function withdrawadd(){
        $uid = $_SESSION['onethink_admin']['user_auth']['uid'];
        $model = M("withdraw");
        if(IS_POST){
            if(!$model->create()) {
                $this->success("数据错误");
            }
            //判断用户积分是否足够
            $score = I("score");
            $where['user_id'] = array("eq",$uid);
            $user_score = M("user_score")->where($where)->getField("score");
            if($user_score < $score){
                $this->error("你的积分不足");
            }
            $model->created = time();
            $model->updated = time();
            $model->user_id = $uid;
            if($model->add()){
                $member_score = M("user_score")->where("user_id=".$uid)->setDec("score",$score);
                //生成积分日志
                $user_score_log['user_id']=$uid;
                $user_score_log['score'] = $score;
                $user_score_log['content'] = '提现扣除积分'.$score;
                $user_score_log['created'] = time();
                $user_score_log['updated'] = time();
                $user_score_log['self_year_month_day'] = date("Y-m-d");
                $user_score_log['type'] = 6;
                M("user_score_log")->add($user_score_log);
                $this->success("操作作成功",U('withdrawindex'));
            }else{
                $this->error("请稍后在试");
            }
        }else{
//            echo  "<pre>";
//            print_r($_SESSION['onethink_admin']['user_info']);
            $this->data = $_SESSION['onethink_admin']['user_info'];
            $this->display();
        }
    }


    //提现申请审核
    public function withdrawedit(){
        $id = I("id");
        if(IS_POST){
            $data['status'] = I("status");
            $data['updated'] = time();
            $data['id'] = $id;
            if(M("withdraw")->save($data)){
                $this->success("操作作成功",U('withdrawindex'));
            }else{
                $this->error("请稍后在试");
            }
        }else{
            $data = M("withdraw")->find($id);
            $this->data = $data;
            $this->display();
        }
    }

    //Vip用户升级高级创客 自助升级
    public function upgradeVipRegister(){
        $model = M("member");
        $uid = $_SESSION['onethink_admin']['user_auth']['uid'];

        if(IS_POST){
            if(I("is_update")==0){
                //查询用户充值积分是否足够
                $where_log['type'] = array("eq",1);
                $where_log['user_id'] = array("eq",$uid);
                if(M("user_score_log")->where($where_log)->sum("score") <16660){
                    $this->error("您当前充值积分不足16660");
                }
            }

            //查询当前城市是否已有高级创客
//        $district_id = I("district_id");
//        $where['district_id'] = array("eq",$district_id);
//        $where['type'] = array("eq",2);
//        $parent_member = M("member")->where($where)->find();
//        if($parent_member){
//            $this->error("所属城市已有高级创客存在，暂不能升级");
//        }
            if($model->create()){
                $model->type =2;
                $model->pay_status =1;
                $model->reg_status =2;
                if(!$model->where("uid=".$uid)->save()){
                    $this->error('用户修改失败！');
                } else {
                    //修改用户所属权限表
                    M("auth_group_access")->where("uid=".$uid)->setField("group_id",2);
                    $model_login = A("Admin/Public");
                    if(I("is_update")==1){
                        $model_login->logout("申请成功，审核后可登录！");
                    }else{
                        $model_login->logout("升级成功，请重新登录！");
                    }
                }
            }else{
                $this->error($model->getError);
            }

        }else{
            $data = M("member")->where("uid = ".$uid)->find();
            $data['username'] = M("ucenter_member")->where("id=".$uid)->getField("username");
            $where['uesr_id'] = array("eq",$uid);
            $where['type'] = array("eq",1);
            $total_num = M("user_score_log")->where($where)->sum("score");
            if($total_num>16660 && $data['type'] ==3){
                $this->level_type = 2;
            }
            if($total_num<16660 && $data['type'] ==3){
                $this->level_type = 1;
            }

            $this->list = M("district")->where("upid=0")->select();
            $data['city_name'] = M("district")->where("id=".$data['city_id'])->getField("name");
            $data['district_name'] = M("district")->where("id=".$data['district_id'])->getField("name");

            $data['totalscore'] = M("user_score")->where("user_id=".$uid)->getField("score");
            $this->data = $data;
            $this->display("upgradeVipRegister");
        }

    }

}
