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
class BuhuoController extends AdminController {

    /**
     *创客补货管理
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */
    public function index(){
        $nickname       =   I('username');
        $map['username']    =   array('like', '%'.(string)$nickname.'%');
        $map['type']    =   array('eq', '1');
        $list   = $this->lists('Buhuo', $map);
        int_to_string($list);
        $this->assign('_list', $list);
        $this->meta_title = '补货信息';
        $this->display();
    }

    /**
     * 创客补货审核
     * ger $code 错误编码
     * @return string        错误信息
     */
    public function edit(){
        $id = I("id");
        if(IS_POST){
            $data['status'] = I("status");
            $data['updated'] = time();
            $data['id'] = $id;
            if(M("buhuo")->save($data)){
                $buhuo_data = M("buhuo")->find($id);
                $user_score_log_obj = M("user_score_log");
                $user_score_log_data['user_id'] = $buhuo_data['user_id'];
                $user_score_log_data['self_year_month_day'] = date("Y-m-d");
                $user_score_log_data['score'] = $buhuo_data['score'];
                $user_score_log_data['content'] = '创客补货充值积分';
                $user_score_log_data['type'] = 1;
                $user_score_log_obj->add($user_score_log_data);
                M("member")->where("uid=".$data['user_id'])->setField("status",1);

                $this->success("操作作成功",U('index'));
            }else{
                $this->error("请稍后在试");
            }
        }else{
            $data = M("buhuo")->find($id);
            $this->data = $data;
            $this->display();
        }

    }

    /**
     * 创客退货列表
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */
    public function tuihuoindex(){
        $nickname       =   I('username');
        $map['type']    =   array('eq', '2');
        $map['username']    =   array('like', '%'.(string)$nickname.'%');
        $list   = $this->lists('Buhuo', $map);
        int_to_string($list);
        $this->assign('_list', $list);
        $this->meta_title = '补货信息';
        $this->display();
    }

    /**
     * 创客退货审核
     * @param  integer $code 错误编码
     * @return string        错误信息
     */
    public function tuihuoedit(){
        $id = I("id");
        if(IS_POST){
            $data['status'] = I("status");
            $data['updated'] = time();
            $data['id'] = $id;
            if(M("buhuo")->save($data)){
                $this->success("操作作成功",U('tuihuoindex'));
            }else{
                $this->error("请稍后在试");
            }
        }else{
            $data = M("buhuo")->find($id);
            $this->data = $data;
            $this->display();
        }

    }


}
