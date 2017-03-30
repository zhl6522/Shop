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
class UserController extends AdminController {

    /**
     * 用户管理首页
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */
    public function index(){
//        dump($_SESSION);
        if($_SESSION['onethink_admin']['user_info']['type']==2){
            $this->redirect("vipindex");
        }

        $type = I("type")?I("type"):1;
        $uids = M("auth_group_access")->where("group_id=".$type)->getField("uid",true);
        $map['uid'] = array("in",$uids);
        $nickname       =   I('nickname');
        $map['status']  =   array('egt',0);
        if(is_numeric($nickname)){
            $map['uid|nickname']=   array(intval($nickname),array('like','%'.$nickname.'%'),'_multi'=>true);
        }else{
            $map['nickname']    =   array('like', '%'.(string)$nickname.'%');
        }

        $list   = $this->lists('Member', $map);
//        echo M()->_sql();
        int_to_string($list);
        $this->assign('_list', $list);
        $this->type = $_SESSION['onethink_admin']['user_info']['type'];

        $this->types = $type;
        $this->meta_title = '用户信息';
        $this->display();
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
                $user = array('uid' => $uid, 'nickname' => $username, 'status' => 1,'type'=>1);
                if(!M('Member')->add($user)){
                    $this->error('用户添加失败！');
                } else {
                    $data['uid'] = $uid;
                    $data['group_id'] = I("type");
                    M("auth_group_access")->add($data);
                    $this->success('用户添加成功！',U('index'));
                }
            } else { //注册失败，显示错误信息
                $this->error($this->showRegError($uid));
            }
        } else {
            $this->type = I("type")?I("type"):1;
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

    /**
     * 完善个人信息
     * @param  integer $code 错误编码
     * @return string        错误信息
     */
    public function updateInfo(){
        $id = I("id");
        $model = M("member");
        if(IS_POST){
            if($model->create()){
                $model->updated = time();
                if($model->save()){
                    $this->success("修改成功");
                }else{
                    $this->error("系统繁忙 ，请稍后在试");
                }
            }else{
                $error = $model->getError();
                $this->error($error);
            }
        }else{
            $where['uid'] = array("eq",$id);
            $data = $model->where($where)->find();
            $this->data = $data;
            $this->display();
        }
    }

    /**
     * Vip用户详情
     * @param  integer $code 错误编码
     * @return string        错误信息
     */
    public function vipedit(){
        $uid = I("uid");
        if(IS_POST){
            $data['reg_status'] = I("reg_status");
            $data['pay_status'] = I("pay_status");
            $data['updated'] = time();
            if(I("is_update")==1){//升级创客审核
                //更新用户状态
                $data['is_update'] = 2;
                M("member")->where("uid = ".$uid)->save($data);
                //添加充值积分记录，
                $score_log['user_id'] =$uid;
                $score_log['self_year_month_day'] = date("Y-m-d",time());
                $score_log['score'] =16600;
                $score_log['content'] = 'vip申请升级创客充值';
                $score_log['type'] =1;
                $score_log['created'] =time();
                $score_log['updated'] =time();
                M("user_score_log")->add($score_log);
                //添加充值积分
                M("user_score")->where("user_id = ".$uid)->setInc("score",16600);
                $this->success("操作作成功",U('User/index?type=2'));exit;
            }

            if(M("member")->where("uid = ".$uid)->save($data)){
                $where['user_id'] = array("eq",$uid);
                $where['first'] = array("eq",1);
                $data['updated'] = time();
                $data['pay_status'] = 1;
                $data['reg_status'] = 2;
                M("goods_order")->where($where)->save($data);
                $where_shopping['user_id'] = array("eq",$uid);
                M("shopping")->where($where_shopping)->delete();
                $this->success("操作作成功",U('User/index?type=2'));
            }else{
                $this->error("请稍后在试");
            }
        }else{
            $this->type = $_SESSION['onethink_admin']['user_info']['type'];
            $data = M("member")->where("uid = ".$uid)->find();
            $data['username'] = M("ucenter_member")->where("id=".$uid)->getField("username");
            $data['totalsocre'] = M("user_score")->where("user_id=".$uid)->getField("score");
            $data['province_name'] = M("district")->where("id=".$data['province_id'])->getField("name");
            $data['city_name'] = M("district")->where("id=".$data['city_id'])->getField("name");
            $data['district_name'] = M("district")->where("id=".$data['district_id'])->getField("name");


            $this->data = $data;
            $this->display();
        }

    }

//    创客下级人员理
    public function vipindex(){
//        dump($_SESSION);
        $vip_code = $_SESSION['onethink_admin']['user_info']['vip_code'];
        $map['pid_code'] = array("eq",$vip_code);
        $list   = $this->lists('Member', $map);
        int_to_string($list);
        $this->assign('_list', $list);
        $this->meta_title = '用户信息';
        $this->display();
    }

    //    管理员下的创客下级人员理
    public function adminvipindex(){
        $pid_code = I("vip_code");
        $map['pid_code'] = array("eq",$pid_code);
        $list   = $this->lists('Member', $map);
        int_to_string($list);
        $this->assign('_list', $list);
        $this->meta_title = '用户信息';
        $this->display();
    }

}
