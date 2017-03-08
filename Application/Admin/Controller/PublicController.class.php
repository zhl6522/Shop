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
 * 后台首页控制器
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
class PublicController extends \Think\Controller {

    /**
     * 后台用户登录
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */
    public function login($username = null, $password = null, $verify = null){
        if(IS_POST){
            /* 检测验证码 TODO: */
//            if(!check_verify($verify)){
//                $this->error('验证码输入错误！');
//            }

            /* 调用UC登录接口登录 */
            $User = new UserApi;
            $uid = $User->login($username, $password);
            if(0 < $uid){ //UC登录成功
                /* 登录用户 */
                $Member = D('Member');
                if($Member->login($uid)){ //登录用户
                    //TODO:跳转到登录前页面
                    $info = M("member")->where("uid=".$uid)->find();
                    $_SESSION['onethink_admin']['user_info'] = $info;
//                    如果是vip用户
                    if($info['type'] ==2 || $info['type'] ==3){
                        if($info['reg_status'] ==-1){
                            $this->redirect("Public/upgradeVipRegister");
                        }
                        if($info['reg_status'] ==0){
                            $this->redirect("Public/vipRegister1");
                        }
                        if($info['reg_status'] ==1 ){
                            $this->redirect("Public/vipRegister2");
                        }
                        if($info['reg_status'] ==2 && $info['pay_status'] ==0){
                            $this->redirect("Public/vipRegister2");
                        }
                    }
                    if($info['status'] ==2){
                        $this->redirect("Public/checklogin");
                    }
                    $this->checktime($info);

                    $this->success('登录成功！', U('Index/index'));
                } else {
                    $this->error($Member->getError());
                }

            } else { //登录失败
                switch($uid) {
                    case -1: $error = '用户不存在或被禁用！'; break; //系统级别禁用
                    case -2: $error = '密码错误！'; break;
                    default: $error = '未知错误！'; break; // 0-接口参数错误（调试阶段使用）
                }
                $this->error($error);
            }
        } else {
            if(is_login()){
                $this->redirect('Index/index');
            }else{
                /* 读取数据库中的配置 */
                $config	=	S('DB_CONFIG_DATA');
                if(!$config){
                    $config	=	D('Config')->lists();
                    S('DB_CONFIG_DATA',$config);
                }
                C($config); //添加配置
                
                $this->display();
            }
        }
    }

    /* 退出登录 */
    public function logout(){
        if(is_login()){
            D('Member')->logout();
            session('[destroy]');
            $this->success('退出成功！', U('login'));
        } else {
            $this->redirect('login');
        }
    }

    public function verify(){
        $verify = new \Think\Verify();
        $verify->entry(1);
    }

    //普通用户注册
    public function register($username = '', $password = '', $repassword = '', $email = '', $verify = ''){
        if(!C('USER_ALLOW_REGISTER')){
            $this->error('注册已关闭');
        }
        if(IS_POST){ //注册用户
            /* 检测验证码 */
//            if(!check_verify($verify)){
//                $this->error('验证码输入错误！');
//            }

            /* 检测密码 */
            if($password != $repassword){
                $this->success('密码和重复密码不一致！');exit;
            }

            /* 调用注册接口注册用户 */
            $User = new UserApi;
            $uid = $User->register($username, $password, $email);
            if(0 < $uid){ //注册成功
                $user_arr = array('uid' => $uid, 'nickname' => $username, 'status' => 1,'type'=>4);
                if(!M('Member')->add($user_arr)){
                    $this->success('用户添加失败！',U('login'));exit;
                } else {
                    $data['uid'] = $uid;
                    $data['group_id'] = 4;
                    M("auth_group_access")->add($data);
                    $this->success('用户添加成功！',U('login'));exit;
                }
            } else { //注册失败，显示错误信息
                $this->success($this->showRegError($uid));
            }
        } else { //显示注册表单
            $this->display();
        }
    }


    //VIP用户注册
    public function vipRegister(){
//        if(!C('USER_ALLOW_REGISTER')){
//            $this->error('注册已关闭');
//        }
        if(IS_POST){ //注册用户
            $password = I("password");
            $repassword = I("repassword");
            $username = I("username");
            $type = I("type");
            /* 检测密码 */
            if($password != $repassword){
                $this->error('密码和重复密码不一致！');
            }
            /* 检测手机验证码 */
            if($_SESSION['phone_code'] != md5(I('code'))){
                $this->error('手机验证码错误！');
            }

            $where['vip_code'] = array("eq",I("vip_code"));
            if(M("member")->where($where)->find()){
                $this->error("vip编码已存在，请重新生成");
            }

            if($type==3){
                $where['vip_code'] = array("eq",I("pid_code"));
                $where['type'] = array("eq",2);
                $parent_member = M("member")->where($where)->find();
                if(!$parent_member){
                    $this->error("推荐编码不存在，请重新填写");
                }
            }


            //同一个市只能有一个高级创客
            $where_chuangke['type'] = array("eq",2);
            $where_chuangke['city_id'] = array("eq",I("city"));
            $member_chuangke = M("member")->where($where_chuangke)->find();
            if($type==2 && $member_chuangke){

            }

            /* 调用注册接口注册用户 */
            $User = new UserApi;
            $uid = $User->register($username, $password);
            if(0 < $uid){ //注册成功
                $model = M("member");
                if($model->create()){
                    $model->status =1;
                    $model->created =time();
                    if(!$model->add()){
                        $this->error('用户添加失败！');
                    } else {
                        $data['uid'] = $uid;
                        $data['group_id'] = I("type");
                        M("auth_group_access")->add($data);
//
                        $User = new UserApi;  //注册成功，马上登录
                        $login_uid = $User->login($username, $password);
                        $Member = D('Member');
                        $Member->login($login_uid);
                        $info = M("member")->where("uid=".$login_uid)->find();
                        $_SESSION['onethink_admin']['user_info'] = $info;

                        //添加 邀请关系
                        $user_invite_user['user_id'] = $uid;
                        $user_invite_user['invite_user_id'] =$parent_member['uid'];
                        $user_invite_user['created'] =time();
                        $user_invite_user['updated'] =time();
                        M("user_invite_user")->add($user_invite_user);


                        //添加 用户表数据
                        $user_score['user_id'] = $uid;
                        $user_score['score'] =0;
                        $user_score['created'] =time();
                        $user_score['updated'] =time();
                        M("user_score")->add($user_score);

                        $this->success('用户添加成功！',U('vipRegister1'));
                    }
                }else{
                    $this->error($model->getError);
                }

            } else { //注册失败，显示错误信息
                $this->error($this->showRegError($uid));
            }
        } else { //显示注册表单
            $this->list = M("district")->where("upid=0")->select();
            $this->display();
        }
    }

    //VIP 用户完提交证信息
    public function vipRegister1(){
//        dump($_SESSION);
        if(IS_POST){
            $id = $_SESSION['onethink_admin']['user_auth']['uid'];
            $pictrue = A("Home/File");
            $member_obj = M("member");
            $info = $pictrue->uploadPicture();
            if($info['status'] != 0 ){ //所有错错误信息
                $this->error($info['info']);
            }else {
                $package_id = I("package_id");
                //查询套餐信息
//                $package_data = M("package")
//                    ->alias("p")
//                    ->join("pre_goods as g on g.package_id =p.id")
//                    ->field("sum(g.package_price) as price ,p.*")
//                    ->find();
                $package_data = M("package")->find($package_id); //套餐信息

                if($package_id ==1){  //1为高级创客注册 2为vip注册
                    $member_data['type'] = 2;
                    $order_data['total'] = $package_data['package_price']*0.5; //注册高级创客
                }else{
                    $member_data['type'] = 3;
                    $order_data['total'] = $package_data['package_price'];
                }
                $member_data['id_num_thumb'] = $info['data']['id_num_thumb']['path'];
                $member_data['reg_status'] = 1;
                $member_data['updated'] = time();
                $member_obj->where("uid=".$id)->save($member_data); //保存图片信息
                M("auth_group_access")->where("uid=".$id)->setField("group_id",$member_data['type']);

                //生成订单信息
                $order_data['user_id'] = $_SESSION['onethink_admin']['user_auth']['uid'];
                $order_data['order_code'] = time().$package_id.$order_data['user_id'].rand(00000,99999);
                $order_data['package_id'] = $package_id;
                $order_data['created'] = time();
                $order_data['updated'] = time();
                $order_data['first'] = 1;
//                $goods_ids = M("goods")->where("package_id")->getField("id",true);
//                $order_data['goods_id'] = implode(",",$goods_ids);
                $res1 = M("goods_order")->add($order_data);

                //生成订单附属表信息
                $shopping_data['user_id'] = $_SESSION['onethink_admin']['user_auth']['uid'];
                $shopping_data['package_id'] = $package_id;
                $shopping_data['created'] = time();
                $shopping_data['updated'] = time();
                $res2 = M("shopping")->add($shopping_data);
                if(!$res1 && !$res2){
                   $this->success('订单生成失败!');
                }
                $this->success('操作成功！',U('vipRegister2'));

            }
        }else{
            $type = $_SESSION['onethink_admin']['user_info']['type'];
            $this->type = $type;
            //获取前四个套餐信息
            if($type ==2){
                $package_data = M("package")->find(1);
            }else{
                $package_data = M("package")->find(2);
            }
            $this->data = $package_data;
            $this->display();
        }
    }

    //VIP 用户完提交证信息
    public function vipRegister2(){
        $uid = $_SESSION['onethink_admin']['user_auth']['uid'];
        $info =  $info = M("member")->where("uid=".$uid)->find();
        $this->info = $info;
        $this->display();
    }

    //支付接口
    public function pay(){
        $success_url = "http://".$_SERVER['SERVER_NAME']."/Admin/Public/successPay";
        $cancel_url = $_SERVER['SERVER_NAME']."/Admin/Public/cancelPay";
        $order = time().rand(0000,9999);
        require_once('pingpp/init.php');
        \Pingpp\Pingpp::setApiKey('sk_live_Pa5GGOmTGWHGiTCGeDvXLOqP');
        \Pingpp\Pingpp::setPrivateKeyPath('/data/develop/php/zs.code/rsa_private_key.pem');
        $json =  \Pingpp\Charge::create(array('order_no'  => $order,
                'amount'    => 1,//订单总金额, 人民币单位：分（如订单总金额为 1 元，此处请填 100）
                'app'       => array('id' => 'app_Dq18KCqf5mzTD8SK'),
                'channel'   => 'alipay_pc_direct',
                'currency'  => 'cny',
                'client_ip' => '103.235.232.91',
                'subject'   => 'pen',
                'body'      => 'this is a pen',
                'extra'=>array(
                    'success_url'=>$success_url,
//                    'enable_anti_phishing_key '=>'',
//                    'exter_invoke_ip '=>'103.235.232.91',
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
      $member = M("member");
      $id = $_SESSION['onethink_admin']['user_auth']['uid'];
      $member_data  = $member->where("uid =".$id)->find();
      $data['updated'] = time();
      $data['pay_status'] = 1;
      $data['status'] = 2;
      $where_member['user_id'] = array("eq",$id);
      $where_member['first'] = array("eq",1);
      $member->where($where_member)->save($data);

      //vip 注册反积分给高级创客
      if($member_data['type'] ==3){
          $where['vip_code'] = array("eq",$member_data['pid_code']);
          $parent_member = $member->where($where)->find();
          $score_log['user_id'] =$parent_member['uid'];
          $score_log['self_year_month_day'] = date("Y-m-d",time());
          $score_log['score'] =414;
          $score_log['content'] = '邀请VIP注册成功返现积分';
          $score_log['type'] =2;
          $score_log['created'] =time();
          $score_log['updated'] =time();
          M("user_score_log")->add($score_log);
//          echo M()->_sql();
          M("user_score")->where("user_id=".$parent_member['uid'])->setInc("score",414);

          $where_shopping['user_id'] = array("eq",$id);
          M("shopping")->where($where_shopping)->delete();
          $this->redirect("Index/index");
      }
    }
    //更新支付接品
    public function canclePay(){
        echo  "支付取消";
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

    //加载地址信
    public function shi(){
        $sheng = M('district')->where('upid='.I('upid'))->select();
        $this->assign('sheng',$sheng);
        $this->display();
    }
    public function xian(){
        $sheng = M('district')->where('upid='.I('upid'))->select();
        $this->assign('sheng',$sheng);
        $this->display();
    }

//    生成vip编码
    public function producecode(){
        $code = M("member")->where("type=2 or type =3")->order("uid desc")->limit(1)->getField("vip_code");
        if(!$code){
            $code = '00000000';
        }else{
            $code = intval($code)+1;
        }
        echo   $code;

    }

    //检测当前用户创客是否 过期若过期则让期失效
    public function checktime($info){
        $long_time = strtotime("+12 months",$info['created'])-time(); //小余0 已注册有12个月
//        dump(strtotime("+12 months",$info['created']));
//        dump($long_time);exit;
        if($info['type'] ==2 && $long_time <0 ){
            $max = time();
            $min = strtotime("-12 months",$max);
            $where['created'] = array("betwwen","$min,$max");
            $where['type'] = 1;
            $score = M("pre_user_score_log")->where($where)->sum("score");
            if($score < 30000*100){
                M("member")->where("uid=".$info['uid'])->setField("status",2);
                $uids = M("user_invite_user")->wehre("invite_user_id=".$info['uid'])->getField("user_id",true);
                $where_users['uid'] = array("in",$uids);
                M("member")->where($where_users)->setField("status",-1);
                $this->redirect("Public/checklogin");
            }
        }
    }

    //提交补货页面
    public function checklogin(){
        if(IS_POST){
            $model = M("buhuo");
            if($model->create()){
                $model->user_id = $_SESSION['onethink_admin']['user_auth']['uid'];
                $model->created = time();
                if($model->add()){
                    $this->success("提交成功，请等待审核");
                }else{
                    $this->error("系统繁忙 ，请稍后在试");
                }
            }else{
                $error = $model->getError();
                $this->error($error);
            }
        }else{
            $this->display();
        }

    }

    /**
     * 发送短信接口
     * @author  <15347096062@163.com>
     */
    public function sendPhone(){
        header("content-type:text/html;charset=utf-8");
//        $phone = I("post.phone");
        $phone = $_REQUEST["phone"];
        if(!preg_match("/1[34578]{1}\d{9}$/", $phone)){
            $this->ajaxReturn(array("info"=>"手机号不正确","data"=>'',"status"=>1));
        }
        $str = rand(1000, 9999);
        $content =rawurlencode("您的验证码是：" . $str . "。有效期为90秒，如非本人操作，请忽略。【超觉英语】");
        $res = send_phone_code($phone,$content);
        if($res['State']==0){
            $_SESSION['phone_code'] = md5($str);
            $this->ajaxReturn(array("info"=>"短信发送成功","data"=>'',"status"=>0));
        }else{
            $this->ajaxReturn(array("info"=>$res['MsgState'],"data"=>'',"status"=>1));
        }
    }

    /**
     * 忘记密码
     * @author  <15347096062@163.com>
     */
    public function forgetpassword(){
        if(IS_POST){
            $phone = I("phone");
            $code = I("code");
            $password = I("password");
            $where['username'] = array("eq",$phone);
            $user_info = M("ucenter_member")->where($where)->find();
//            echo M()->_sql();exit;
            if(!$user_info){
                $this->error("手机号用户不存在");
            }

            if(md5($code) != $_SESSION['phone_code'] || !$code){
                $this->error("手机验证码错误");
            }
            $data['id'] = $user_info['id'];
            $data['password'] = md5(sha1($password) . 'k3o|z=<9Xy[8%Wn>5e!2,R~l*E^J{vhqNd+7p(?D');
            $data['update_time'] = time();
            M("ucenter_member")->save($data);
            $this->success("密码修改成功",U('login'));
        }else{
            $this->display();
        }
    }

    public function test(){
//        echo md5(sha1('123456').'k3o|z=<9Xy[8%Wn>5e!2,R~l*E^J{vhqNd+7p(?D');
//        $res = M("member")->field("sum(vip_code)+sum(pid_code) as sum")->select();
        $res = M("member")->sum("vip_code+pid_code");
        echo M()->_sql();
        var_dump($res);

    }


}
