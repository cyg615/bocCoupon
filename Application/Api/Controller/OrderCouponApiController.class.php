<?php

namespace Api\Controller;
use Think\Controller;
//use Phalcon\DI;
//use Vendor\Util\RedisClient;
use Home\Org\Util\CurlUtil;
//use Vendor\Util\Sqlserver;



class OrderCouponApiController  extends Controller{
    const SAPURL = "127.0.0.1";
    const INSTANCEURL="127.0.0.1:8080";
    public $baseApi = 'http://testcoupon.g-town.com.cn/';

    function _initialize() {
        header('Content-Type:text/html;Charset=utf-8');
    }
    public function index()
    {
        echo 9999;exit;
    }
    public function sendVirtualCard()
    {
        Vendor('Util.RedisClient');
        $redis =    \RedisClient::instance()->connect();
        Vendor('Util.TService');
        Vendor('Util.Crypt3Des');
        $cry = \Crypt3Des::instance('admin123','sdfgrwqe');
        $order_no = trim(I('order_no'));
        $orderInfo=M('coupon_order o')->join("join vrc_goods g on o.goods_id=g.id")->join("join vrc_venue_user u on o.user_id=u.id")->where("o.order_no='".$order_no ."' and o.coupon_sn='' ")->field("g.sequence,g.name as goods_name,o.phone,o.num,u.nickname,o.order_no,o.id as order_id")->find();
        $inteface = 'coupon/buy';
        $data = array('goodsSQ'=>$orderInfo['sequence'],'appId'=>'test','amount'=>intval($orderInfo['num']));
        $sign = $this->createSign('test', $data);
        $data['sign'] = $sign;
        $res = \TService::instance()->requestService($this->baseApi.$inteface,$data);
        if($res['httpCode']==200)
        {
            $coupon=$res['data']['data'];
            if(!isset($coupon['orderId']) || $coupon['orderId']=='')
            {
                echo '库存不足';
            }else{
                $inteface = 'coupon/querycoupon';
                $data = array('appId'=>'test','orderNo'=>$coupon['orderId'],'page'=>1);
                $sign = $this->createSign('test', $data);
                $data['sign'] = $sign;
                $res = \TService::instance()->requestService($this->baseApi.$inteface,$data);
                if($res['httpCode']==200)
                {
                    $respond['coupon_list']=$res['data']['couponList'];
                }
                $orderInfo['couponList']=$res['data']['couponList'];
                try {
                    $redis->lPush('sendvirtcard', json_encode($orderInfo));
                    echo 'success';
                }catch(Exception $e)
                {
                    echo '加入短信队列失败';
                }
            }
        }
        exit;


    }


    /**
     * 检查手机号
     * @param unknown $phone
     */
    function _checkPhone($phone){
        if(preg_match("/^1[3456789]{1}\d{9}$/",$phone))
        {
            return true;
        }else{
            return false;
        }
    }

    function post($uri,$data){
        $ch = curl_init ();
        curl_setopt ( $ch, CURLOPT_URL, $uri );
        curl_setopt ( $ch, CURLOPT_POST, 1 );
        curl_setopt ( $ch, CURLOPT_HEADER, 0 );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $data );
        $return = curl_exec ( $ch );
        curl_close ( $ch );
        return $return;
    }



    function sendVirtualCardSMSByTask(){
        ini_set('default_socket_timeout', -1);
        import("@.ORG.Util.RedisClient");
        Vendor('Util.Crypt3Des');
        $cry = \Crypt3Des::instance('admin123','sdfgrwqe');
        Vendor('Util.RedisClient');
        $redis =    \RedisClient::instance()->connect();
        //$redis =    RedisClient::instance()->connect();
        $check=true;
        $i=$redis->lSize('sendvirtcard');
        while ($i>0){
            $res = $redis->lPop('sendvirtcard');
            if($res){
                $data = json_decode($res,true);
                foreach($data['couponList'] as $k=>$v)
                {

                    if(isset($data['phone']) && $this->_checkPhone($data['phone']))
                    {

                        $condition='id='.$data['order_id'];
                        M('coupon_order')->where($condition)->save(array('coupon_sn'=>$cry->decrypt($v['code'])));
                        if(strlen($v['password'])>0) {
                            $msg = "您好!您在海娱平台订购的" . $data['goods_name'] . "，订单号" . $data['order_no'] . "。卡号：" . $cry->decrypt($v['code']) . "，密码：".base64_decode($v['password'])."。请妥善保管,勿泄露。如有疑问请拨打400-006-6830.";
                        }else{
                            $msg = "您好!您在海娱平台订购的" . $data['goods_name'] . "，订单号" . $data['order_no'] . "。卡号：" . $cry->decrypt($v['code']) . "。请妥善保管,勿泄露。如有疑问请拨打400-006-6830.";
                        }
                        $this->_sendSMS($data['phone'], $msg);
                        if($res)
                        {

                        }
                    }else{

                    }
                }
            }else{
                // $check = false;
            }
            $i--;

        }
        exit('SUCCESS');

    }



    /**
     *
     * @param unknown $phone
     * @param unknown $content
     * @return multitype:
     */
    function _sendSMS($phone,$content){
        $api = "http://sms.g-town.com.cn/sms/?service=Message.Send";
        $params = array(
            'phone'  =>$phone,
            'content'=>$content,
            'sysCode' =>'OA'
        );
        return  http($api, $params,'POST');
    }


    /**
     *
     */
    function checkSendVirtualCardSMSProcess(){

        $command = 'wmic process  get caption,commandline /value | findstr "sendVirtualCard"';
        exec($command,$output);
        print_r($output);
        if(count($output)<3){
            exec('php C:\wamp\www\g-townOA\cli.php virtualCardApi/sendVirtualCardSMSByTask');
        }
    }
    /**
     *
     * @param unknown $subject
     * @param unknown $from
     * @param unknown $xml
     * @param string  $url
     * @param string  $returnXML
     * @return mixed|unknown
     */
    private function http($subject,$from,$xml,$sign='',$url='',$returnArr=true){
        if(!$url) $url=self::INSTANCEURL;
        $header[]="Content-Type: text/xml; charset=utf-8";
        $header[]="Subject:{$subject}";
        $header[]="Message-From:{$from}";
        $header[]="SignKey:{$sign}";
        $header[]="Content-Length: ".strlen($xml);
        // error_log(var_export($header,1));
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $res = curl_exec($ch);
        curl_close($ch);
        if($returnArr){
            return json_decode(json_encode((array) simplexml_load_string($res)), true);
        }
        return $res;
    }
    private  function createSign($appkey,$array){
        ksort($array);
        $str = '';
        foreach ($array as $key=>$va){
            $str.=$key.$va;
        }
        $str.=$appkey;
        return md5($str);
    }

}