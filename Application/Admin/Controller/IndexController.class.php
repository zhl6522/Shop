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
class IndexController extends AdminController
{

    /**
     * 后台首页
     * @author zhl <ahfuzl@126.com>
     */
    public function index()
    {
        $menus = M('shop_class')->where('status=1 && pid=0')->getField('id,type,name,level');
        $menu_ids = parent::get_ids($menus, 'id');
        $wheres['pid'] = array('in', $menu_ids);
        $wheres['level'] = 2;
        $wheres['status'] = 1;
        $shop_class = M('shop_class')->where($wheres)->order('id DESC')->getField('id,type,name,level,pid');
        foreach ($menus as $k => $v) {
            foreach ($shop_class as $key => $value) {
                if ($value['pid'] == $k) {
                    $menus[$k]['shop'][$key] = $value;
                }
            }
        }
        shop_to_is_last($menus);
        $map['status'] = array('eq', 1);
        $map['top'] = array('eq', 1);
        $list = M('goods')->where($map)->order('id desc')->limit(10)->getField('id,title,thumb,unit_price');
        $where['status'] = array('eq', 1);
        $package = M('package')->where('package_top = 1')->order('id DESC')->limit(5)->getField('id,name,package_price,package_prices');
        $ids = parent::get_ids($package, 'id');
        $where['package_id'] = array('in', $ids);
        $package_goods = M('goods')->where($where)->order('id DESC')->getField('id,title,thumb,unit_price,package_id');
        foreach ($package as $k => $v) {
            foreach ($package_goods as $key => $value) {
                if ($value['package_id'] == $k) {
                    $package[$k]['goods'][$key] = $value;
                }
            }
        }
        is_last_to_is_last($package);
        $where_picture['status'] = 1;
        $picture = M('banner')->where($where_picture)->order(array('listorder' => 'desc', 'id' => 'desc'))->select();
        $this->assign('_picture', $picture);
        $this->assign('_menus', $menus);
        $this->assign('_list', $list);
        $this->assign('_package', $package);
        $this->meta_title = '管理首页';
        $this->info = $_SESSION['onethink_admin']['user_info'];
        $this->display();
    }

    public function search()
    {
        $nickname = I('post.nickname');
        $map['status'] = array('egt', 0);
        if (!empty($nickname)) {
            $map['title'] = array('like', '%' . (string)$nickname . '%');
        }
        $list = $this->lists('goods', $map);
        int_to_string($list);
        $class_ids = parent::get_ids($list, 'class_id');
        $data = class_to_class_name($class_ids);
        foreach ($list as $k => $v) {
            $list[$k]['class_name'] = $data[$v['class_id']];
        }
        $this->assign('_list', $list);
        $this->meta_title = '商品搜索';
        $this->display();
    }

    public function shop_class()
    {
        $id = I('get.id');
        $map['class_id'] = $id;
        $shop_class = M('shop_class')->where('id=' . $id)->getField('id, pid, name');
        $classs = parent::array_value($shop_class);
        if ($classs[0]['pid'] != 0) {
            $class = self::get_pid($classs[0]['pid']);
        }
        $shop_detail = self::get_id($classs[0]['pid']);
        $class[0]['class'] = $classs;
        $list = $this->lists('goods', $map);
        $this->assign('_list', $list);
        $this->assign('_class', $class);
        $this->meta_title = '商品分类';
        $this->display();
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


    //首页轮播图片管理
    public function bannerindex(){
        $map['status']  =   array('egt',0);

        $list   = $this->lists('Banner', $map,"listorder desc");
//        echo M()->_sql();
        int_to_string($list);
        $this->assign('_list', $list);
        $this->meta_title = 'banner图片';
        $this->display();
    }

    public function banneradd(){
        if(IS_POST){
            if($_FILES['thumb']['name']){
                $pictrue = A("Home/File");
                $info = $pictrue->uploadPicture();
                if($info['status'] != 0 ){ //所有错错误信息
                   $this->error($info['info']);
                }else {
                    $data['thumb'] = $info['data']['thumb']['path'];
                }
            }
            $data['title'] = I("title");
            $data['listorder'] = I("listorder");
            $data['created'] =time();
            M("banner")->add($data);
            $this->success("添加成功",U('bannerindex'));
        }else{
            $this->display();
        }
    }

    public function banneredit(){
        if(IS_POST){
            if($_FILES['thumb']['name']){
                $pictrue = A("Home/File");
                $info = $pictrue->uploadPicture();
                if($info['status'] != 0 ){ //所有错错误信息
                    $this->error($info['info']);
                }else {
                    $data['thumb'] = $info['data']['thumb']['path'];
                }
            }
            $data['id'] = I("id");
            $data['title'] = I("title");
            $data['status'] = I("status");
            $data['listorder'] = I("listorder");
            $data['updated'] =time();
            M("banner")->save($data);
            $this->success("修改成功",U('bannerindex'));
        }else{
            $this->data = M("banner")->find(I("id"));
            $this->display();
        }
    }

    public function video(){
        $where['status'] = array("eq",1);
        $list   = M("video")->where($where)->select();
        $this->list = $list;
        $this->meta_title = '视频列表';
        $this->display();
    }

//    视频添加
    public function addvideo(){
        if(IS_POST){
            if($_FILES['thumb_path']['name']){
                $pictrue = A("Home/File");
                $info = $pictrue->uploadPicture();
                if($info['status'] != 0 ){ //所有错错误信息
                    $this->error($info['info']);
                }else {
                    $data['thumb_path'] = $info['data']['thumb_path']['path'];
                }
            }

            $data['type'] = I("type");
            $data['title'] = I("title");
            $data['description'] = I("description");
            $data['thumb'] = I("thumb");
            $data['listorder'] = I("listorder");
            $data['updated'] =time();
            $data['created'] =time();
            M("video")->add($data);
            $this->success("添加成功",U('indexvideo'));
        }else{
            $where['status'] = array("eq",1);
            $where['pid'] = array("eq",0);
            $shop_class = M("shop_class")->where($where)->select();
            $this->shop_class = $shop_class;
            $this->display();
        }
    }

    //视频列表
    public function indexvideo(){
//        $map['title']  =   array('eq',I('title'));
        $map = array();
        $list   = $this->lists('video', $map,"listorder desc");
//        echo M()->_sql();
        int_to_string($list);
        $this->assign('_list', $list);
        $this->meta_title = '视频列表';
        $this->display();
    }

    //视频修改
    public function editvideo(){
        if(IS_POST){
            if($_FILES['thumb_path']['name']){
                $pictrue = A("Home/File");
                $info = $pictrue->uploadPicture();
                if($info['status'] != 0 ){ //所有错错误信息
                    $this->error($info['info']);
                }else {
                    $data['thumb_path'] = $info['data']['thumb_path']['path'];
                }
            }
            $data['id'] = I("id");
            $data['type'] = I("type");
            $data['title'] = I("title");
            $data['description'] = I("description");
            $data['thumb'] = I("thumb");
            $data['listorder'] = I("listorder");
            $data['status'] = I("status");
            $data['updated'] =time();
            M("video")->save($data);
//            echo M()->_sql();exit;
            $this->success("修改成功",U('indexvideo'));
        }else{
            $where['status'] = array("eq",1);
            $where['pid'] = array("eq",0);
            $shop_class = M("shop_class")->where($where)->select();
            $this->shop_class = $shop_class;
            $this->data = M("video")->find(I("id"));
            $this->display();
        }
    }

}
