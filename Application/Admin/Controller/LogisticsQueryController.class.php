<?php
/**
 * Created by PhpStorm.
 * User: lwb
 * Date: 2017/3/1
 * Time: 14:32
 */

namespace Admin\Controller;


/**
 * 后台物流查询控制器
 *
 */
class LogisticsQueryController extends AdminController
{
    public $EBusinessID = '1279660';
    public $AppKey = 'f693c4e9-5cd2-4466-b5a9-dda8078d708f';
    public $ReqURL = 'http://api.kdniao.cc/Ebusiness/EbusinessOrderHandle.aspx';

////电商ID
//defined('EBusinessID') or define('EBusinessID', '1279660');
////电商加密私钥，快递鸟提供，注意保管，不要泄漏
//defined('AppKey') or define('AppKey', 'f693c4e9-5cd2-4466-b5a9-dda8078d708f');
////请求url
//defined('ReqURL') or define('ReqURL', 'http://api.kdniao.cc/Ebusiness/EbusinessOrderHandle.aspx');
//
////调用查询物流轨迹
////---------------------------------------------
//
//$logisticResult=getOrderTracesByJson();
//echo $logisticResult;

//---------------------------------------------

    /**
     * Json方式 查询订单物流轨迹
     */
    function getOrderTracesByJson($com, $num)
    {
        $requestData = "{'OrderCode':'','ShipperCode':'{$com}' ,'LogisticCode': '{$num}'
}";
//        dump($requestData);
        $datas = array(
            'EBusinessID' => $this->EBusinessID,
            'RequestType' => '1002',
            'RequestData' => urlencode($requestData),
            'DataType' => '2',
        );
//        dump($datas);
        $datas['DataSign'] = $this->encrypt($requestData, $this->AppKey);
        $result = $this->sendPost($this->ReqURL, $datas);
        //根据公司业务处理返回的信息......

        return $result;
    }

    /**
     *  post提交数据
     * @param  string $url 请求Url
     * @param  array $datas 提交的数据
     * @return url响应返回的html
     */
    function sendPost($url, $datas)
    {
        $temps = array();
        foreach ($datas as $key => $value) {
            $temps[] = sprintf('%s=%s', $key, $value);
        }
        $post_data = implode('&', $temps);
        $url_info = parse_url($url);
        if (empty($url_info['port'])) {
            $url_info['port'] = 80;
        }
        $httpheader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
        $httpheader .= "Host:" . $url_info['host'] . "\r\n";
        $httpheader .= "Content-Type:application/x-www-form-urlencoded\r\n";
        $httpheader .= "Content-Length:" . strlen($post_data) . "\r\n";
        $httpheader .= "Connection:close\r\n\r\n";
        $httpheader .= $post_data;
        $fd = fsockopen($url_info['host'], $url_info['port']);
        fwrite($fd, $httpheader);
        $gets = "";
        $headerFlag = true;
        while (!feof($fd)) {
            if (($header = @fgets($fd)) && ($header == "\r\n" || $header == "\n")) {
                break;
            }
        }
        while (!feof($fd)) {
            $gets .= fread($fd, 128);
        }
        fclose($fd);
//    dump($gets);
        return $gets;
    }

    /**
     * 电商Sign签名生成
     * @param data 内容
     * @param appkey Appkey
     * @return DataSign签名
     */
    function encrypt($data, $appkey)
    {
        return urlencode(base64_encode(md5($data . $appkey)));
    }


//正式请求url
//    private $AppKey = "";
//    public $powered = '查询数据由：<a href="http://kuaidi100.com" target="_blank">KuaiDi100.Com （快递100）</a> 网站提供 ';
//
//    private function base($url)
//    {
//        if (function_exists('curl_init') == 1) {
//            $curl = curl_init();
//            curl_setopt($curl, CURLOPT_URL, $url);
//            curl_setopt($curl, CURLOPT_HEADER, 0);
//            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
//            curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
//            curl_setopt($curl, CURLOPT_TIMEOUT, 5);
//            $get_content = curl_exec($curl);
//            curl_close($curl);
//            return $get_content;
//        }
//    }

    /**
     * 签收状态
     */
    public function sign()
    {
        $typeCom = I("com");//快递公司代码
        $typeNu = I("num");  //快递单号
        $re = $this->getOrderTracesByJson($typeCom, $typeNu);
        if ($re) {
            if ($temp = json_decode($re, true)) {
                //dump($temp);
                if ($temp["Success"]  && $temp["State"] == 3) {
                    //echo "2";
                    $date = 1;
                } else {
                    $date = 0;
                }
            }else{
                $date = 0;
            }
        } else {
            $date = 0;
        }
        echo $date;
    }

    /**
     * 物流记录
     */
    public function status()
    {
//        echo "3";exit;
        $order = I("order_code");  //订单号
        $logistics = D("order_logistics")->where("order_code={$order} and status=1")->find();
        if($logistics){
            $logistics_ids= D("logistics")->where("status=1")->getField("id,code,name");
            if($logistics_ids){
                foreach($logistics_ids as $k=>$v){
                    if($logistics["logistics_id"]==$v["id"]){
                        $typeCom=$v["code"];
                        $kuaidi=$v["name"];
                    }
                }
            }

            $re = $this->getOrderTracesByJson($typeCom, $logistics["number"]);
//            dump($re);

        }

//        $logistic_time = D("order_logistics")->where("order_code=" . $order . "and status=2 and  number=" . $nu)->field("created,number")->find();
        $order_time = D("goods_order")->where("order_code={$order} and status =1")->field("created")->select();
        if ($order_time[0]["created"]) {
            $order_time = date("Y-m-d H:i:s");
        } else {
            $order_time = 0;
        }
        if ($logistics["created"]) {
            $logistic_time = date("Y-m-d H:i:s");
        } else {
            $logistic_time = 0;
        }
        $first = "您的订单" . $order . "已发送后台，我们会第一时间处理您的订单，请耐心等待......";
        $second = "您的订单由第三方卖家拣货完毕，待出库交付{$kuaidi}，运单号为" . $logistics["number"];
//        $date=array();
        if ($order_time) {
            if ($logistic_time) {
                if ($temp = json_decode($re, true)) {
                    if ($temp["Success"]  && $temp["State"] == 3) {
                        $date = $temp["Traces"];
                    }
                }
                $date[] = array('AcceptTime' => $logistic_time, "AcceptStation" => $second);
            }
            $date[] = array('AcceptTime' => $order_time, "AcceptStation" => $first);
        }

        return $date;
    }
}