<?php
/**
 * Created by PhpStorm.
 * User: lwb
 * Date: 2017/2/16
 * Time: 11:22
 */

namespace Admin\Controller;

class ReportController extends AdminController
{

    public function  index()//销售量统计
    {
        $statistics = M("sales_statistics");
//        $list=array();
        $nows = date("Y-m");
        if (I("begin")) {
            $nows = I("begin");
        }
        $ends = date("Y-m");
        if (I("end")) {
            $ends = I("end");
        }
        if ($_SESSION["onethink_admin"]["user_info"]["type"] == 1) {
            if (IS_POST) {
//                echo "3";
                $begin = get_explode_post_time(I("begin"));
                $end = get_explode_post_time(I("end"));
                $where["status"] = 1;
                if ($begin != $end) {
                    $where["created"] = array(array("egt", $begin), array("elt", $begin));
                    $where["status"] = 1;
                    $last = $statistics->where($where)->field("created")->find();
                    $first = $statistics->where($where)->field("created")->order("id desc")->find();
                    $result = $statistics->where($where)->field("sum(sales_volume) as rang_sales_volume,sum(turnover) as rang_turnover")->select();
//                    dump($result);
                    if ($result) {
                        $list [0]["sales_volume"] = $result[0]["rang_sales_volume"];
                        $list [0]["turnover"] = $result[0]["rang_turnover"];
                        $list [0]["created"] = $first["created"] . '-' . $last["created"];
                    }

                } else {
                    $where["created"] = $begin;
                    $result = $statistics->where($where)->select();
//                    dump($result);
                    if ($result) {
                        $list = $result;
                    }
                }
            } else {
//                echo "6";
                $nowArr = $this->get_explode_time();
                $condition['created'] = $nowArr["last"]["month"];
                $lastmonth_date = $statistics->where($condition)->select();
//                dump($lastmonth_date);
                if (empty($lastmonth_date)) {
                    $re = $this->get_sales_statis_resutl($nowArr["last"]);//上月数据
                    if (!empty($re)) {
                        $statistics->add($re);
                    }
                }
                $list[] = $this->get_sales_statis_resutl($nowArr["now"]);//时时数据
            }
//            dump($list);

            $this->assign("end", $ends);
            $this->assign("nows", $nows);
            $this->assign("list", $list);
            $this->display();

        } else {
            $this->error("用户无权访问", "index/index");

        }


    }

    public function  sort()//商品销售排行
    {
        if ($_SESSION["onethink_admin"]["user_info"]["type"] == 1) {
            $nowArr = $this->get_explode_time();
            $nows = date("Y-m");
            if (I("start")) {
                $nows = I("start");
            }
            $ends = date("Y-m");
            if (I("start")) {
                $nows = I("start");
            }
            if (IS_POST) {
                $begin = get_explode_post_time(I("start"));
                $sale_condition["status"] = 1;
                $sale_condition["month"] = $begin;
                $last_count = M("goods_sale_asc")->where($sale_condition)->count('id');
//                $User = M('User'); // 实例化User对象
                $Page = new \Think\Page($last_count, 10);// 实例化分页类 传入总记录数和每页显示的记录数(25)
                $show = $Page->show();// 分页显示输出
                $list = M("goods_sale_asc")->where($sale_condition)->limit($Page->firstRow . ',' . $Page->listRows)->select();
//              dump($list);
                array_unshift($list, array());
//                dump($list);

                $arr_or_query = 1;
                $this->assign('arr_or_query', $arr_or_query);// 赋值数据集
                $this->assign('list', $list);// 赋值数据集
                $this->assign('page', $show);// 赋值分页输出

            } else {
//                echo"3";
                if (!$sale_asc = M("goods_sale_asc")->where("status=1 and month=" . $nowArr['last']['month'])->select()) {
                    $list = $this->get_sort($nowArr["last"]);
//                       dump($list);
                    if (!empty($list)) {
                        foreach ($list as $asc) {
                            if (!empty($asc)) {
                                $asc_temp["goods_id"] = $asc["id"];
                                $asc_temp["title"] = $asc["title"];
                                $asc_temp["num"] = $asc["num"];
                                $asc_temp["sum"] = $asc["sum"];
                                $asc_temp["month"] = $nowArr["last"]["month"];
                                $asc_temp["status"] = 1;
                                M("goods_sale_asc")->add($asc_temp);
                            }
                        }
                    }
                }
                $list = $this->get_sort($nowArr["now"]);
                array_unshift($list, array());
                $count = count($list) - 1;
                $Page = new \Think\Page($count, 10);// 实例化分页类 传入总记录数和每页显示的记录数(25)
                $show = $Page->show();// 分页显示输出
////                dump($show);
                $this->assign('page', $show);// 赋值分页输出
                $be = 0;
                if ($_GET['p']) {
                    $be = ($_GET['p'] - 1) * 10;
                }
                $arr_or_query = 0;
                $this->assign('arr_or_query', $arr_or_query);// 赋值数据集
                $this->assign("be", $be);
                $this->assign("list", $list);

            }

            $this->assign("end", $ends);
            $this->assign("start", $nows);
            $this->display();

        }


    }

    public function  status()
    {
        $nows = date("Y-m");
        if (I("begin")) {
            $nows = I("begin");
        }
        $ends = date("Y-m");
        if (I("end")) {
            $ends = I("end");
        }


        if ($_SESSION["onethink_admin"]["user_info"]["type"] == 1) {
            $nowArr = $this->get_explode_time();
            if (IS_POST) {
                $begin = get_explode_post_time(I("begin"));
                $end = get_explode_post_time(I("end"));
                if ($begin != $end) {
                    $where["month"] = array(array("egt", $begin), array("elt", $end));
                    $where["stauts"] = 1;
//                    $where["user_id"] = $_SESSION["onethink_admin"]["user_info"]["uid"];
                    $last = M("sale_status")->where($where)->field("month")->find();

                    $first = M("sale_status")->where($where)->field("month")->order("id desc")->find();
//                dump($first);

                    $result = M("sale_status")->where($where)->field("title,sum(first_and_second) as first_and_second_num ,sum(second) as second_total,sum(first_and_second) as first_total")->group("type")->select();
//                    echo $order_static->_sql();
//                    dump($result);
                    $list=$this->add_arr(2,$result);
//                    dump($list );
                    if ($list) {
                        $list["month"] = $first["month"] . '-' . $last["month"];
                        $status=1;
                    }
//                    dump($re );
//                dump($list);
                } else {
                    $where["month"] = $begin;
                    $where["status"] = 1;
                    $result = M("sale_status")->where($where)->select();
//                    dump($result);
//                   echo $order_static->_sql();
                    if ($result) {
                        $list=$this->add_arr(1,$result);
//                        $status=0;
                    }
                }
            } else {
//                echo "2";
                if (!M("sale_status")->where("status=1 and month=" . $nowArr["last"]["month"])->select()) {
                    $re = $this->get_status($nowArr['last']);
//                   dump($re);
                    if ($re) {
                        foreach ($re as $rk => $rv) {
                            if ($rv) {
                                $rv["status"] = 1;
//                               dump($rv);
                                M("sale_status")->add($rv);
                            }
                        }
                    }
                }

                $re = $this->get_status($nowArr['now']);
//                dump($re);
                $list=$this->add_arr(1,$re);
//                dump($list);
                $status=0;
            }
            $this->assign("status", $status);
            $this->assign("nows", $nows);
            $this->assign("list", $list);
            $this->assign("end", $ends);
            $this->display();
        } else {
            $this->error("用户无权访问", "index/index");
        }
    }

    public function  member()//会员订单额、订单量统计
    {
        $nows = date("Y-m");
        if (I("begin")) {
            $nows = I("begin");
        }
        $ends = date("Y-m");
        if (I("end")) {
            $ends = I("end");
        }
        $nowArr = $this->get_explode_time();
        $order_static = M("member_order_static");
        if (IS_POST) {
//               echo "3";
            $begin = get_explode_post_time(I("begin"));
            $end = get_explode_post_time(I("end"));

            $where["status"] = 1;
            if ($begin != $end) {
                $where["created"] = array(array("egt", $begin), array("elt", $end));
                $where["user_id"] = $_SESSION["onethink_admin"]["user_info"]["uid"];
                $last = $order_static->where($where)->field("created")->find();
                $first = $order_static->where($where)->field("created")->order("id desc")->find();
//                dump($first);
                $result = $order_static->where($where)->field("sum(order_num) as rang_num ,sum(order_yuan) as rang_yuan")->select();
//                    echo $order_static->_sql();
                if ($result) {
                    $list [0]["order_num"] = $result[0]["rang_num"];
                    $list [0]["order_yuan"] = $result[0]["rang_yuan"];
                    $list [0]["created"] = $first["created"] . '-' . $last["created"];
                }
//                dump($list);
            } else {
                $where["created"] = $begin;
                $where["user_id"] = $_SESSION["onethink_admin"]["user_info"]["uid"];
                $result = $order_static->where($where)->select();
//                   echo $order_static->_sql();
                if ($result) {
                    $list = $result;
                }
            }
        } else {
            $where["user_id"] = $_SESSION["onethink_admin"]["user_info"]["uid"];
            $where["status"] = 1;
            $where["created"] = $nowArr["last"]["month"];
            if (!$re = M("member_order_static")->where($where)->select()) {
                $list = $this->get_member_order_static($nowArr["last"]);
//                    dump($list);
                if ($list) {
                    $list = current($list);
                    $list["status"] = 1;
                }
            }

            M("member_order_static")->add($list);
            $list = $this->get_member_order_static($nowArr["now"]);
        }
//        dump($end);
        $this->assign("nows", $nows);
        $this->assign("list", $list);
        $this->assign("end", $ends);
        $this->display();
    }

    public function  register() //注册人数统计
    {
        if ($_SESSION["onethink_admin"]["user_info"]["type"] == 1) {
            $nowArr = $this->get_explode_time();
            $nows = date("Y-m");
            if (I("begin")) {
                $nows = I("begin");
            }
            $ends = date("Y-m");
            if (I("end")) {
                $ends = I("end");
            }
            if (IS_POST) {
                $begin = get_explode_post_time(I("begin"));
                $end = get_explode_post_time(I("end"));

                $where["status"] = 1;
                if ($begin != $end) {
                    $where["created"] = array(array("egt", $begin), array("elt", $end));
                    $last = M("register_static")->where($where)->field("created")->find();
                    $first = M("register_static")->where($where)->field("created")->order("id desc")->find();
//                dump($first);
                    $result = M("register_static")->where($where)->field("sum(num) as rang_num")->select();
//                    echo M("register_static")->_sql();
//                    dump($result);
                    if ($result) {
                        $list [0]["num"] = $result[0]["rang_num"];
                        $list [0]["created"] = $first["created"] . '-' . $last["created"];
                    }
//                    dump($list);
                } else {
                    $where["created"] = $begin;
//                    $where["user_id"] = $_SESSION["onethink_admin"]["user_info"]["uid"];
                    $result = M("register_static")->where($where)->select();
//                   echo $order_static->_sql();
                    if ($result) {
                        $list = $result;
                    }
                }
            } else {
//                    echo "3";
                $where["status"] = 1;
                $where["created"] = $nowArr["last"]["month"];
                if (!$re = M("register_static")->where($where)->select()) {
                    $list = $this->get_member_order_static($nowArr["last"]);
//                    dump($list);
                    if ($list) {
                        $list = current($list);
                        $list["status"] = 1;
                    }
                    M("register_static")->add($list);
                }
                $list = $this->get_member_static($nowArr["now"]);

            }

            $this->assign("nows", $nows);
            $this->assign("list", $list);
            $this->assign("end", $ends);

            $this->display();

        }
    }

    /**
     * 获得商品成交量、成交额
     *
     **/
    private function get_sales_statis_resutl($nowArr)
    {
        $order = M("goods_order");//实力订单表
        $noPackage["package_id"] = 0;
        $noPackage["status"] = 1;
        $noPackage["pay_status"] = 1;
        $noPackage["created"] = array(array("gt", $nowArr["start"]), array("lt", $nowArr["end"]));
        $noPackage["all"] = $order->where($noPackage)->field("sum(order_num) as num ,sum(total) as total")->select();//非套餐商品销量与销售额
//            dump( $noPackage["all"]);
        $noPackage["all"] = current($noPackage["all"]);
//            dump($noPackage["all"]);
        $Package["package_id"] = array("neq", 0);
        $Package["created"] = $noPackage["created"];
        $Package["status"] = 1;
        $Package["pay_status"] = 1;
        $packageTotal = $order->where($Package)->sum("total");//套餐销售总额
        $Package["all"] = $order->where($Package)->group("package_id")->field("package_id,sum(order_num) as num")->select();//各类套餐数量
//            dump($Package["all"]);

        $goods = M("goods");
        $goodsPackageIds = $goods->where("is_package <> 0 and status =1 ")->group("package_id")->field("package_id,count(id) as gid")->select();//套餐内商品数量
//           dump($goodsPackageIds);
        $temp = [];
        foreach ($Package["all"] as $pk => $pv) {
            foreach ($goodsPackageIds as $gk => $gv) {
                if ($pv["package_id"] == $gv["package_id"]) {
                    $packageGoodsNum = $pv["num"] * $gv["gid"];
                    $temp[] = $packageGoodsNum;
                }
            }
        }
        $packageSaleNum = array_sum($temp);//套餐商品 销售量
//            dump($packageSaleNum);
        $saleStatistics["sales_volume"] = $noPackage["all"]["num"] + $packageSaleNum;//成交量
        $saleStatistics["turnover"] = $noPackage["all"]["total"] + $packageTotal;//成交额
        $saleStatistics["created"] = $nowArr["month"];
        $saleStatistics["status"] = 1;

        return $saleStatistics;

    }

    /**
     * 获取当前月字符串
     */
    private function get_explode_time()
    {
        $now = date("Y-m");
        $year_month["start"] = strtotime($now);//当前月开始时间戳
        $year_month["end"] = strtotime(date("Y-m", strtotime("+ 1 month"))) - 1;//当前月结束时间戳

        $last_year_month["start"] = strtotime(date("Y-m", strtotime("- 1 month")));//上月开始时间戳
        $last_year_month["end"] = strtotime(date("Y-m")) - 1;//上月结束时间戳

        $last_month = date('Y-m', ($last_year_month["end"]));
        $last = explode('-', $last_month);
        $last_year_month["month"] = $last[0] . $last[1];

        $now = explode('-', $now);
        $year_month["month"] = $now[0] . $now[1];
        $temp["now"] = $year_month;
        $temp["last"] = $last_year_month;
//       dump($temp);
        return $temp;
    }

    private function get_member_order_static($nowArr)
    {
        $condition["created"] = array(array("gt", $nowArr["start"]), array("lt", $nowArr["end"]));
        $condition["status"] = 1;
        $condition["pay_status"] = 1;
        $condition["user_id"] = $_SESSION["onethink_admin"]["user_info"]["uid"];
        $list = M("goods_order")->where($condition)->field("user_id,count(id) as order_num,sum(total) as order_yuan")->select();
        if ($list) {
            $list[0]["created"] = $nowArr["month"];
        }
        return $list;
    }

    private function get_member_static($nowArr)
    {
        $condition["reg_time"] = array(array("gt", $nowArr["start"]), array("lt", $nowArr["end"]));
        $condition["status"] = 1;
        $list = M("ucenter_member")->where($condition)->field("count(id) as num")->select();
        if ($list) {
            $list[0]["created"] = $nowArr["month"];
        }
        return $list;
    }

    private function get_sort($nowArr)
    {
        $goods_info = M('goods')->field('id,title,is_package,package_id,package_price')->select();//获取有效商品信息
        $conditon["created"] = $nowArr["month"];
        $conditon["package_id"] = 0;
        $order_good_sum = D("goods_order_shop")->where($conditon)->group("goods_id")->field("goods_id ,sum(num) as unit_num ,sum(goods_price) as unit_order_sum")->select();//订单 单个商品 销售额 销售量
        $conditon["package_id"] = array("neq", 0);
        $order_package_good_sum = D("goods_order_shop")->where($conditon)->group("package_id")->field("sum(num) as package_good_id_num,package_id")->select();//订单 套餐个数
//    dump($order_package_good_sum);
        foreach ($goods_info as $info_key => $info) {//商品添加字段
            $goods_info[$info_key]["unit_num"] = 0;//订单计算单个商品数量 初始值为0
            $goods_info[$info_key]["unit_order_sum"] = 0;//订单计算单个商品总价 初始值为0
            $goods_info[$info_key]["package_good_id_num"] = 0;//订单计算套餐商品数量 初始值为0
            $goods_info[$info_key]["month"] = $nowArr["month"];
        }

        foreach ($goods_info as $info_key => $info) {
            foreach ($order_good_sum as $order_good_key => $order_good) {
                if ($info["id"] == $order_good["goods_id"]) {
                    $goods_info[$info_key]["unit_num"] = $order_good["unit_num"];
                    $goods_info[$info_key]["unit_order_sum"] = $order_good["unit_order_sum"];
                }
            }
            foreach ($order_package_good_sum as $order_package_good_key => $order_package_good) {
                if ($info["package_id"] == $order_package_good["package_id"]) {
                    $goods_info[$info_key]["package_good_id_num"] = $order_package_good["package_good_id_num"];
                }
            }
            $goods_info[$info_key]["num"] = $goods_info[$info_key]["unit_num"] + $goods_info[$info_key]["package_good_id_num"];//计算商品总销售量
            $goods_info[$info_key]["sum"] = $goods_info[$info_key]["unit_order_sum"] + ($goods_info[$info_key]["package_good_id_num"] * $goods_info[$info_key]["package_price"]);//计算商品总销售额

        }
        $good_order_as_sum = $this->my_sort($goods_info, "sum");
//       return $good_order_as_sum;

        return $good_order_as_sum;
//       dump($good_order_as_sum) ;


    }

    private function my_sort($arrays, $sort_key, $sort_order = SORT_DESC, $sort_type = SORT_NUMERIC)
    {
        if (is_array($arrays)) {
            foreach ($arrays as $array) {
                if (is_array($array)) {
                    $key_arrays[] = $array[$sort_key];
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }
        array_multisort($key_arrays, $sort_order, $sort_type, $arrays);
        return $arrays;
    }

    private function get_status($nowArr)//经营状况
    {
        $condition['month'] = $nowArr["month"];
        $condition['first'] = 1;
        $condition['m.type'] = array('neq', 1);

        $first = M("member")//首次注册
        ->alias("m")
            ->join("pre_goods_order o on m.uid=o.user_id ")
            ->join("pre_auth_group a on m.type=a.id")
            ->where($condition)
            ->group("m.type")
            ->field("m.type,o.month,o.first,o.total,o.pay_status,o.status,sum(o.total) as mototal,a.title ")
            ->select();

        $first_types = self::get_ids($first, "type");
//        dump($first_types);
        $auth_group = M("auth_group")->where("id!=1 and status=1")->field("id,title")->select();//用户组

//        dump($auth_group);
        $condition['first'] = 0;
        $second = M("member")//>=2流水
        ->alias("m")
            ->join("pre_goods_order o on m.uid=o.user_id ")
            ->join("pre_auth_group a on m.type=a.id")
            ->where($condition)
            ->group("m.type")
            ->field("m.type,o.month,o.first,o.total,o.pay_status,o.status,sum(o.total) as mototal,a.title ")
            ->select();
//        echo  M()->_sql();
        $second_types = self::get_ids($second, "type");
        foreach ($auth_group as $k => $v) { // 组合数据 1
            if (!in_array($v['id'], $first_types)) {
                $first[] = array("title" => $v["title"], "mototal" => 0, 'month' => $nowArr["month"], "type" => $v["id"]);
            }
            if (!in_array($v['id'], $second_types)) {
                $second[] = array("title" => $v["title"], "mototal" => 0, 'month' => $nowArr["month"], "type" => $v["id"]);
            }

        }
//        dump($first);
//        dump($second);
        foreach ($first as $fk => $fv) {//组合数组 2
            foreach ($second as $sk => $sv) {
                if ($fv["type"] == $sv["type"]) {
                    $temp[] = array('title' => $sv["title"], 'type' => $sv["type"], 'month' => $sv["month"], "first" => $fv["mototal"], "second" => $sv["mototal"]);
                }
            }
        }
        foreach ($temp as $tk => $tv) {
            if ($tv) {
                $temp[$tk]["first_and_second"] = $tv["first"] + $tv["second"];
            }
        }
//        dump($temp);
        return $temp;
    }
    private function add_arr($a=1,$re){ //数组追加数据
//        dump($re);
        if($a==1){
            if($re && is_array($re)){
                foreach($re as $k=>$v){
                    if(is_array($v)){
                        $f_temp[]=$v["first"];
                        $s_temp[]=$v["second"];
                        $s_f_temp[]=$v["first_and_second"];
                    }
                }
            }
            $re["first_sum"]=array_sum($f_temp);
            $re["second_sum"]=array_sum( $s_temp);
            $re["s_f_temp"]=array_sum( $s_f_temp);
        }else{
            if($re && is_array($re)){
                foreach($re as $k=>$v){
                    if(is_array($v)){
                        $f_temp[]=$v["first_total"];
                        $s_temp[]=$v["second_total"];
                        $s_f_temp[]=$v["first_and_second_num"];
                    }
                }
            }
            $re["first_sum"]=array_sum($f_temp);
            $re["second_sum"]=array_sum( $s_temp);
            $re["s_f_temp"]=array_sum( $s_f_temp);
        }
//      dump($re);
       return $re;
    }
}