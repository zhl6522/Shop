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
class GoodsController extends AdminController
{

    /**
     * 用户管理首页
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */
    public function index()
    {
        $nickname = I('nickname');
        $map['status'] = array('egt', 0);
        if (!empty($nickname)) {
            if (is_numeric($nickname)) {
                $map['id|title'] = array(intval($nickname), array('like', '%' . $nickname . '%'), '_multi' => true);
            } else {
                $map['title'] = array('like', '%' . (string)$nickname . '%');
            }
        }
        $list = $this->lists('goods', $map);
        int_to_string($list);
        $class_ids = parent::get_ids($list, 'class_id');
        $data = class_to_class_name($class_ids);
        foreach ($list as $k => $v) {
            $list[$k]['class_name'] = $data[$v['class_id']];
        }
        $this->assign('_list', $list);
        $this->meta_title = '商品列表信息';
        $this->display();
    }

    /**
     * 标签管理首页
     * @author zhl <ahfuzl@126.com>
     */
    public function label()
    {
        $nickname = I('nickname');
        $map['status'] = array('egt', 0);
        if (!empty($nickname)) {
            if (is_numeric($nickname)) {
                $map['id|title'] = array(intval($nickname), array('like', '%' . $nickname . '%'), '_multi' => true);
            } else {
                $map['title'] = array('like', '%' . (string)$nickname . '%');
            }
        }
        $list = $this->lists('label', $map);
        int_to_string($list);
        $this->assign('_list', $list);
        $this->meta_title = '商品标签信息';
        $this->display();
    }

    /**
     * 分类管理首页
     * @author zhl <ahfuzl@126.com>
     */
    public function shop_class()
    {
        $pid = I('get.pid', 0);
        if ($pid) {
            $data = M('shop_class')->where("id={$pid}")->field(true)->find();
            $this->assign('data', $data);
        }
        $title = trim(I('get.name'));
        $all_menu = M('shop_class')->getField('id,name');
        $map['pid'] = $pid;
        if ($title)
            $map['title'] = array('like', "%{$title}%");
        $list = M("Menu")->where($map)->field(true)->order('sort asc,id asc')->select();
        int_to_string($list, array('hide' => array(1 => '是', 0 => '否'), 'is_dev' => array(1 => '是', 0 => '否')));
        if ($list) {
            foreach ($list as &$key) {
                if ($key['pid']) {
                    $key['up_title'] = $all_menu[$key['pid']];
                }
            }
            $this->assign('list', $list);
        }
        // 记录当前列表页的cookie
        Cookie('__forward__', $_SERVER['REQUEST_URI']);

        $nickname = I('nickname');
        //$map['level'] = array('eq', 1);
        $map['status'] = array('egt', 0);
        if (!empty($nickname)) {
            if (is_numeric($nickname)) {
                $map['id|name'] = array(intval($nickname), array('like', '%' . $nickname . '%'), '_multi' => true);
            } else {
                $map['name'] = array('like', '%' . (string)$nickname . '%');
            }
        }
        $list = $this->lists('shop_class', $map);
        int_to_string($list);
        $this->assign('_list', $list);
        $this->meta_title = '商品标签信息';
        $this->display();
    }

    /**
     * 添加商品行为
     * @author zhl <ahfuzl@126.com>
     */
    public function add()
    {
        if (IS_POST) {
            if(!I('post.thumb')) {
                $this->error('请上传分类图标');
            }
            if(!I('post.class_id')) {
                $this->error('请选择所属分类');
            }
            $package = M('package');
            $is_package = I('post.is_package');
            $goods = D('goods');
            $goodsdetail = D('goods_detail');
            $goodsstock = D('goods_stock');
            $package_id = I('post.package_id');
            $commodity_name = I('post.commodity_name');
            $brand = I('post.brand');
            $net_content = I('post.net_content');
            $origin = I('post.origin');
            $type = I('post.type');
            $specifications = I('post.specifications');
            $flavor = I('post.flavor');
            $describe = I('post.describe');
            $introduce = I('post.introduce');
            $model = new \Think\Model();
            $model->startTrans();
            if($package_id !=0) {
                $package_num = $goods->where('package_id = '.$package_id)->count();
                if($package_num >=6) {
                    $this->error('套餐商品最多5个');
                }
                $data_package_values = $package->where('id='.$package_id)->find();
                $data_package = array();
                $data_package['id'] = $package_id;
                $data_package['package_price'] = $data_package_values['package_price'] + I('post.unit_price');
                $data_package['package_prices'] = $data_package_values['package_prices'] + I('post.package_price');
                $package->save($data_package);
            }
            $data = $goods->create();
            $data2 = $goodsdetail->create();
            $data3 = $goodsstock->create();
            if ($data && $data2 && $data3) {
                if($is_package ==0) {
                    $goods->package_id = 0;
                    $goods->package_price = 0;
                }
                $id = $goods->add();
                $goodsdetail->goods_id = $id;
                $goodsdetail->commodity_name = $commodity_name;
                $goodsdetail->brand = $brand;
                $goodsdetail->net_content = $net_content;
                $goodsdetail->origin = $origin;
                $goodsdetail->type = $type;
                $goodsdetail->specifications = $specifications;
                $goodsdetail->flavor = $flavor;
                $goodsdetail->describe = $describe;
                $goodsdetail->introduce = $introduce;
                $pic = (count($_POST['pic']) > 5) ? 5 : count($_POST['pic']);
                for ($i = 1; $i <= $pic; $i++) {
                    $j = 'pic_' . $i;
                    $goodsdetail->$j = $_POST['pic'][$i - 1];
                }
                $id2 = $goodsdetail->add();
                $goodsstock->goods_id = $id;
                $id3 = $goodsstock->add();
                if ($id && $id2 && $id3) {

                    $model->commit();
                    $this->success('新增成功', U('index'));
                } else {
                    $model->rollback();
                    $this->error('新增失败');
                }
            } else {
                $this->error($goods->getError());
            }
        } else {
            $menus = M('shop_class')->where('status=1')->field(true)->select();
            $package = M('package')->where('status=1')->field(true)->select();
            $menus = D('Common/Tree')->toFormatTrees($menus);
            $menus = array_merge(array(0 => array('id' => 0, 'title_show' => '顶级菜单')), $menus);
            $this->assign('Menus', $menus);
            $this->assign('package', $package);
            $this->meta_title = '新增商品';
            $this->display();
        }
    }

    /**
     * 查看商品详情
     * @author zhl <ahfuzl@126.com>
     */
    public function show()
    {
        $id = I('get.id');
        if (empty($id)) {
            $this->redirect('index');
        }
        $goods = M('goods')->where('status=1 and id=' . $id)->field(true)->select();
//        int_to_string($goods);
//        is_package_to_is_package_name($goods);
        $goodsdetail = M('goods_detail')->where('status=1 and goods_id=' . $goods[0]['id'])->field(true)->select();
        $goodsstock = M('goods_stock')->where('status=1 and goods_id=' . $goods[0]['id'])->field(true)->select();
        $menus = M('shop_class')->where('status=1')->field(true)->select();
        $package = M('package')->where('status=1')->field(true)->select();
        $menus = D('Common/Tree')->toFormatTrees($menus);
        $menus = array_merge(array(0 => array('id' => 0, 'title_show' => '顶级菜单')), $menus);
        $this->assign('Menus', $menus);
        $this->assign('package', $package);
        $this->assign('goods', $goods);
        $this->assign('goodsdetail', $goodsdetail);
        $this->assign('goodsstock', $goodsstock);
        $this->meta_title = '新增商品';
        $this->display();
    }

    /**
     * 编辑商品详情
     * @author zhl <ahfuzl@126.com>
     */
    public function editShop()
    {
        $id = I('get.id');
        if (IS_POST) {
            //print_r($_POST);exit;
            $package = M('package');
            $id = I('post.id');
            $is_package = I('post.is_package');
            $detail_id = I('post.detail_id');
            $stock_id = I('post.stock_id');
            $package_id = I('post.package_id');
            $goods = D('goods');
            $goodsdetail = D('goods_detail');
            $goodsstock = D('goods_stock');

            $commodity_name = I('post.commodity_name');
            $brand = I('post.brand');
            $net_content = I('post.net_content');
            $origin = I('post.origin');
            $type = I('post.type');
            $specifications = I('post.specifications');
            $flavor = I('post.flavor');
            $describe = I('post.describe');
            $introduce = I('post.introduce');

            $model = new \Think\Model();
            $model->startTrans();
            $old_package = $goods->where('id='.$id)->find();
            if($package_id !=0) {
                $package_num = $goods->where('package_id = '.$package_id)->count();
                if($package_num >6) {
                    $this->error('套餐商品最多6个');
                }

                if($old_package['package_id'] != $package_id) {
                    $data_package_values = $package->where('id='.$old_package['package_id'])->find();
                    $data_package = array();
                    $data_package['id'] = $old_package['package_id'];
                    $data_package['package_price'] = $data_package_values['package_price'] - $old_package['unit_price'];
                    $data_package['package_prices'] = $data_package_values['package_prices'] - $old_package['package_price'];
                    $package->save($data_package);

                    $data_package_values2 = $package->where('id='.$package_id)->find();
                    $data_package2 = array();
                    $data_package2['id'] = $package_id;
                    $data_package2['package_price'] = $data_package_values2['package_price'] + I('post.unit_price');
                    $data_package2['package_prices'] = $data_package_values2['package_prices'] + I('post.package_price');
                    $package->save($data_package2);
                }
            } else {
                if($old_package['package_id'] != 0) {
                    $data_package_values = $package->where('id='.$old_package['package_id'])->find();
                    $data_package = array();
                    $data_package['id'] = $old_package['package_id'];
                    $data_package['package_price'] = $data_package_values['package_price'] - $old_package['unit_price'];
                    $data_package['package_prices'] = $data_package_values['package_prices'] - $old_package['package_price'];
                    $package->save($data_package);
                }
            }
            $data = $goods->create();
            $data2 = $goodsdetail->create();
            $data3 = $goodsstock->create();
            if ($data && $data2 && $data3) {
                $goods->id = $id;
                if($is_package ==0) {
                    $goods->package_id = 0;
                    $goods->package_price = 0;
                }
                $goodsdetail->goods_id = $id;
                $goodsdetail->id = $detail_id;
                $goodsdetail->commodity_name = $commodity_name;
                $goodsdetail->brand = $brand;
                $goodsdetail->net_content = $net_content;
                $goodsdetail->origin = $origin;
                $goodsdetail->type = $type;
                $goodsdetail->specifications = $specifications;
                $goodsdetail->flavor = $flavor;
                $goodsdetail->describe = $describe;
                $goodsdetail->introduce = $introduce;
                $pic = (count($_POST['pic']) > 5) ? 5 : count($_POST['pic']);
                for ($i = 1; $i <= $pic; $i++) {
                    $j = 'pic_' . $i;
                    $goodsdetail->$j = $_POST['pic'][$i - 1];
                }
                $goodsstock->goods_id = $id;
                $goodsstock->id = $stock_id;
                if ($goods->save() !== false && $goodsdetail->save() !== false && $goodsstock->save() !== false) {
                    $model->commit();
                    $this->success('编辑成功', U('index'));
                } else {
                    $model->rollback();
                    $this->error('编辑失败');
                }
            } else {
                $this->error($goods->getError());
            }
        } else {
            if (empty($id)) {
                $this->redirect('index');
            }
            $goods = M('goods')->where('status=1 and id=' . $id)->field(true)->select();
//        int_to_string($goods);
//        is_package_to_is_package_name($goods);
            $goodsdetail = M('goods_detail')->where('status=1 and goods_id=' . $goods[0]['id'])->field(true)->select();
            $goodsstock = M('goods_stock')->where('status=1 and goods_id=' . $goods[0]['id'])->field(true)->select();
            $menus = M('shop_class')->where('status=1')->field(true)->select();
            $package = M('package')->where('status=1')->field(true)->select();
            $menus = D('Common/Tree')->toFormatTrees($menus);
            $menus = array_merge(array(0 => array('id' => 0, 'title_show' => '顶级菜单')), $menus);
            $this->assign('Menus', $menus);
            $this->assign('package', $package);
            $this->assign('goods', $goods);
            $this->assign('goodsdetail', $goodsdetail);
            $this->assign('goodsstock', $goodsstock);
        }
        $this->meta_title = '编辑商品';
        $this->display();
    }

    /**
     * 添加标签行为
     * @author zhl <ahfuzl@126.com>
     */
    public function addClassAction()
    {
        if (IS_POST) {
            $Menu = D('shop_class');
            $model = new \Think\Model();
            $model->startTrans();
            $data = $Menu->create();
            if ($_POST['pid'] == 0) {
                $Menu->level = 1;
                $type = M('shop_class')->order('type DESC')->getField('type');
                $Menu->type = $type['type'] + 1;
            } else {
                $pid = intval($_POST['pid']);
                $level = M('shop_class')->where('id = ' . $pid)->find();
                $Menu->level = $level['level'] + 1;
                $Menu->type = $level['type'];
            }

            if ($data) {
                $id = $Menu->add();
                if ($id) {
                    $model->commit();
                    // S('DB_CONFIG_DATA',null);
                    $this->success('新增成功', Cookie('__forward__'));
                } else {
                    $model->rollback();
                    $this->error('新增失败');
                }
            } else {
                $this->error($Menu->getError());
            }
        } else {
            $this->assign('info', array('pid' => I('pid')));
            $menus = M('shop_class')->where('status=1')->field(true)->select();
            $menus = D('Common/Tree')->toFormatTrees($menus);
            $menus = array_merge(array(0 => array('id' => 0, 'title_show' => '顶级菜单')), $menus);
            $this->assign('Menus', $menus);
            $this->meta_title = '新增分类';
            $this->display('editshopclass');
        }
    }

    /**
     * 添加标签行为
     * @author zhl <ahfuzl@126.com>
     */
    public function editClassAction($id = 0)
    {
        if (IS_POST) {
            $Menu = D('shop_class');
            $model = new \Think\Model();
            $model->startTrans();
            if ($_POST['pid'] == 0) {
                $Menu->level = 1;
                $type = $Menu->order('type DESC')->find();
                $Menu->type = $type['type'] + 1;
            } else {
                $pid = intval($_POST['pid']);
                $level = $Menu->where('pid = ' . $pid)->find();
                $Menu->level = $level['level'] + 1;
                $Menu->type = $level['type'];
            }
            $data = $Menu->create();
            if ($data) {
                if ($Menu->save() !== false) {
                    $model->commit();
                    $this->success('更新成功', Cookie('__forward__'));
                } else {
                    $model->rollback();
                    $this->error('更新失败');
                }
            } else {
                $this->error($Menu->getError());
            }
        } else {
            $info = array();
            /* 获取数据 */
            $info = M('shop_class')->where('status=1')->field(true)->find($id);
            $menus = M('shop_class')->where('status=1')->field(true)->select();
            $menus = D('Common/Tree')->toFormatTrees($menus);

            $menus = array_merge(array(0 => array('id' => 0, 'title_show' => '顶级菜单')), $menus);
            $this->assign('Menus', $menus);
            if (false === $info) {
                $this->error('获取后台菜单信息错误');
            }
            $this->assign('info', $info);
            $this->meta_title = '编辑分类';
            $this->display('editshopclass');
        }
    }

    /**
     * 删除分类
     * @author zhl <ahfuzl@126.com>
     */
    public function del()
    {
        $id = array_unique((array)I('id', 0));
        if (empty($id)) {
            $this->error('请选择要操作的数据!');
        }
        $res = M('shop_class');
        $res->updated = time();
        $res->status = -1;
        $map = array('id' => array('in', $id));
        if ($insertid = $res->where($map)->save()) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败！');
        }
    }

    /**
     * 删除商品行为
     * @author zhl <ahfuzl@126.com>
     */
    public function delShop()
    {
        $id = array_unique((array)I('id', 0));
        if (empty($id)) {
            $this->error('请选择要操作的数据!');
        }
        $res = M('goods');
        $res->updated = time();
        $res->status = -1;
        $map = array('id' => array('in', $id));
        if ($insertid = $res->where($map)->save()) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败！');
        }
    }


    public function addClass()
    {
        $res = D('shop_class');
        if ($vo = $res->create()) {
            $res->created = time();
            if ($res->add()) {
                $this->success('商品标签新增成功！', U('/Admin/Goods/shop_class'));
            } else {
                $this->error('用户注册失败，返回上级页面');
            }
        } else {
            $this->error($res->getError());
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
        empty($nickname) && $this->error('请输入昵称');
        empty($password) && $this->error('请输入密码');

        //密码验证
        $User = new UserApi();
        $uid = $User->login(UID, $password, 4);
        ($uid == -2) && $this->error('密码不正确');

        $Member = D('Member');
        $data = $Member->create(array('nickname' => $nickname));
        if (!$data) {
            $this->error($Member->getError());
        }

        $res = $Member->where(array('uid' => $uid))->save($data);

        if ($res) {
            $user = session('user_auth');
            $user['username'] = $data['nickname'];
            session('user_auth', $user);
            session('user_auth_sign', data_auth_sign($user));
            $this->success('修改昵称成功！');
        } else {
            $this->error('修改昵称失败！');
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
     * 新增标签行为
     * @author huajie <banhuajie@163.com>
     */
    public function addLabelAction()
    {
        $this->meta_title = '新增标签';
        $this->assign('meta_title', $this->meta_title);
        $this->assign('data', null);
        $this->display();
    }


    /**
     * 编辑标签行为
     * @author huajie <banhuajie@163.com>
     */
    public function editLabelAction()
    {
        $id = I('get.id');
        empty($id) && $this->error('参数不能为空！');
        $data = M('label')->field(true)->find($id);

        $this->assign('data', $data);
        $this->meta_title = '编辑标签';
        $this->display();
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
     * 添加标签行为
     * @author huajie <banhuajie@163.com>
     */
    public function addLabel()
    {
        $res = D('label');
        if ($vo = $res->create()) {
            $res->created = time();
            if ($res->add()) {
                $this->success('商品标签新增成功！', U('/Admin/Goods/label'));
            } else {
                $this->error('用户注册失败，返回上级页面');
            }
        } else {
            $this->error($res->getError());
        }
    }

    /**
     * 更新标签行为
     * @author huajie <banhuajie@163.com>
     */
    public function editLabel()
    {
        $res = D('label');
        if ($res->create()) {
            $res->updated = time();
            if ($insertid = $res->save()) {
                $this->success('商品标签更新成功！', U('/Admin/Goods/label'));
            } else {
                $this->error('更新失败');
            }
        } else {
            $this->error($res->getError());
        }
    }

    /**
     * 删除标签行为
     * @author huajie <banhuajie@163.com>
     */
    public function delLabel()
    {
        $res = M('label');
        $id = I('get.id');
        $res->updated = time();
        $res->status = -1;
        if ($insertid = $res->where('id=' . $id)->save()) {
            $this->success('商品标签删除成功！');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * 会员状态修改
     * @author 朱亚杰 <zhuyajie@topthink.net>
     */
    public function changeLabelStatus($method = null)
    {
        $id = array_unique((array)I('id', 0));
        if (in_array(C('LABEL_ADMINISTRATOR'), $id)) {
            $this->error("不允许对超级管理员执行该操作!");
        }
        $id = is_array($id) ? implode(',', $id) : $id;
        if (empty($id)) {
            $this->error('请选择要操作的数据!');
        }

        $map['id'] = array('in', $id);
        switch (strtolower($method)) {
            case 'deletelabel':
                $this->delete('label', $map);
                break;
            default:
                $this->error('参数非法');
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

}
