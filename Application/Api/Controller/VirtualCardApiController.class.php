<?php

namespace Api\Controller;
use Think\Controller;
//use Vendor\Util\RedisClient;
use Home\Org\Util\CurlUtil;
//use Vendor\Util\Sqlserver;



/**
 * 虚拟卡相关数据接口 2017-07-31  
 *
 * ---------------作废------------------
 */

class VirtualCardApiController  extends Controller{
    const SAPURL = "127.0.0.1";
    const INSTANCEURL="127.0.0.1:8080";
    public $baseApi = 'http://couponsms.g-town.com.cn:8888/';
    function  index()
    {
//        $msg='您好！这里是美世合作商户，您的猫眼电影通兑券50元兑换码为：401061377614710，请妥善保管。如有任何问题请拨打：400-006-6830，祝您生活愉快！';
//        $phone="18636958586";
//
//        $this->_sendSMS($phone, $msg);
//        $redis =    \RedisClient::instance()->connect();
//
//        $i=$redis->lSize('sendvirtcard');
//        echo $i;
//
//        while ($i>0) {
//            $res = $redis->lPop('sendvirtcard');
//        }
    }
    function _initialize() {
        header('Content-Type:text/html;Charset=utf-8');

    }
    public function sendVirtualCard()
    {
        Vendor('Util.RedisClient');
        $redis =    \RedisClient::instance()->connect();

        Vendor('Util.TService');
        Vendor('Util.Crypt3Des');
        $cry = \Crypt3Des::instance('admin123','sdfgrwqe');
        $id = intval(I('id'));
        if(!$id) exit('Invalid Requesst ');
        $sql="SELECT  T3.U_CardCode,T4.CardFName,T2.u_orderid+'~'+CONVERT(NVARCHAR(50),T2.docentry)+'~'+CONVERT(NVARCHAR(50),T2.lineid) ordersn,''shippingcode,''shippingname,T2.U_CSTNAME username,T2.U_address address,T2.u_zipcode zipcode,t2.u_phone phone,T2.U_OTPHONE tel,'' shippingfee,t2.U_STATE province,t2.u_city city,t2.U_County region,T0.u_itemcode item_no,t0.u_qty num FROM [@ti_z0131] t0 LEFT JOIN [@ti_z0130] T1 ON t0.docentry=t1.docentry LEFT JOIN [@ti_z0081] T2 ON t0.U_BsEntry=t2.docentry AND t0.u_bsline=t2.lineid left join [@ti_Z0080] T3 ON T2.docentry=t3.docentry left join ocrd T4 on t3.u_cardcode=t4.cardcode WHERE  t0.u_whscode='A02' AND T0.DocEntry=$id and  T3.U_CardCode in ('C20045')";
        Vendor('Util.MSSql');
        $sqlser = new \MSSql();
        $sqlser->Connect('192.168.1.10:1433','sa2','sasa','GT');
        $query=$sqlser->Query($sql);
        $cardList=$sqlser->GetRow($query);
        if($cardList){
            foreach ($cardList as $cardinfo) {
                $ordernum = M('sap_virtualcard_api')->where("orderSn = '" . $cardinfo['ordersn'] . "'")->getField("count(*)");
                if ($ordernum < 1) {
                    $inteface = 'coupon/buy';
                    $data = array('goodsSQ' => $cardinfo['item_no'], 'appId' => 'test', 'amount' => intval($cardinfo['num']));
                    $sign = $this->createSign('test', $data);
                    $data['sign'] = $sign;
                    $res = \TService::instance()->requestService($this->baseApi . $inteface, $data);
                    if ($res['httpCode'] == 200) {
                        $coupon = $res['data']['data'];
                        if (!isset($coupon['orderId']) || $coupon['orderId'] == '') {
                            M('sap_virtualcard_api')->add(
                                array(
                                    'itemNo' => $cardinfo['item_no'],
                                    'username' => $cardinfo['username'],
                                    'orderSn' => $cardinfo['ordersn'],
                                    'address' => $cardinfo['address'],
                                    'phone' => $cardinfo['phone'],
                                    'shopName' => $cardinfo['CardFName'],
                                    'goodsName' => isset($cardinfo['cardName']) ? $cardinfo['cardName'] : '',
                                    'msg' => '库存不足',
                                    'status' => 2
                                )
                            );
                        } else {
                            $inteface = 'coupon/querycoupon';
                            $data = array('appId' => 'test', 'orderNo' => $coupon['orderId'], 'page' => 1);
                            $sign = $this->createSign('test', $data);
                            $data['sign'] = $sign;
                            $res = \TService::instance()->requestService($this->baseApi . $inteface, $data);
                            if ($res['httpCode'] == 200) {
                                $respond['coupon_list'] = $res['data']['couponList'];
                            }
                            $cardinfo['couponList'] = $res['data']['couponList'];

                            try {

                                $redis->lPush('sendvirtcard', json_encode($cardinfo));
                            } catch (Exception $e) {
                                M('virtualcard')->add(
                                    array(

                                        'itemNo' => $cardinfo['item_no'],
                                        'cardNo' => base64_encode($cry->decrypt($cardinfo['cardNo'])),
                                        'sendTime' => date('Y-m-d H:i:s'),
                                        'username' => $cardinfo['username'],
                                        'orderSn' => $cardinfo['ordersn'],
                                        'address' => $cardinfo['address'],
                                        'phone' => $cardinfo['phone'],
                                        'shopName' => $cardinfo['CardFName'],
                                        'goodsName' => isset($cardinfo['cardName']) ? $cardinfo['cardName'] : '',
                                        'msg' => '短信发送成功',
                                        'status' => 1

                                    )
                                );
                                $mquery = "insert into Import_Z0090(OrderId,DocEntry,Lineid,Phone)  values('" . strtok($cardinfo['ordersn'], '~') . "','" . strtok($cardinfo['ordersn'], '~') . "','" . strtok($cardinfo['ordersn'], '~') . "','" . $cardinfo['phone'] . "')";
                                $sqlser->query($mquery);
                                if (isset($cardinfo['phone']) && isset($cardinfo['content']) && $this->_checkPhone($cardinfo['phone'])) {
                                    $data['phone'] = rtrim($cardinfo['phone']);
                                    $data['phone'] = ltrim($cardinfo['phone']);
                                    $data['phone'] = substr($cardinfo['phone'], 0, 11);
                                    $msg = "尊敬的" . $data['username'] . "您好!您在" . $cardinfo['CardFName'] . "订购的" . $cardinfo['cardName'] . "，订单号" . strtok($cardinfo['ordersn'], '~') . "。卡号：" . $cry->decrypt($cardinfo['cardNo']) . "。请妥善保管,勿泄露。如有疑问请拨打400-006-6830.";
                                    $res = $this->_sendSMS($cardinfo['phone'], $msg);

                                    if ($res) {

                                    }
                                } else {
                                    M('virtualcard')->add(
                                        array(

                                            'itemNo' => $cardinfo['item_no'],
                                            'cardNo' => base64_encode($cry->decrypt($cardinfo['cardNo'])),
                                            'sendTime' => date('Y-m-d H:i:s'),
                                            'username' => $cardinfo['username'],
                                            'orderSn' => $cardinfo['ordersn'],
                                            'address' => $cardinfo['address'],
                                            'phone' => $cardinfo['phone'],
                                            'shopName' => $cardinfo['CardFName'],
                                            'goodsName' => isset($cardinfo['cardName']) ? $cardinfo['cardName'] : '',
                                            'msg' => '手机号不正确',
                                            'status' => 2
                                        )
                                    );
                                }
                            }
                        }
                    } else {
                        M('sap_virtualcard_api')->add(
                            array(
                                'itemNo' => $cardinfo['item_no'],
                                'username' => $cardinfo['username'],
                                'orderSn' => $cardinfo['ordersn'],
                                'address' => $cardinfo['address'],
                                'phone' => $cardinfo['phone'],
                                'shopName' => $cardinfo['CardFName'],
                                'goodsName' => isset($cardinfo['cardName']) ? $cardinfo['cardName'] : '',
                                'msg' => '网络故障',
                                'status' => 2
                            )
                        );
                    }
                }
            }
        }

    }


    /**
     * 检查手机号码
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
        set_time_limit(0);
        import("@.ORG.Util.RedisClient");
        Vendor('Util.Crypt3Des');
        $cry = \Crypt3Des::instance('admin123','sdfgrwqe');
        Vendor('Util.RedisClient');
        $redis =    \RedisClient::instance()->connect();

        $i=$redis->lSize('sendvirtcard');

        while ($i>0){
            $res = $redis->lPop('sendvirtcard');
            if($res){
                $data = json_decode($res,true);

                foreach($data['couponList'] as $k=>$v)
                {
                    M('sap_virtualcard_api')->add(
                        array(

                            'itemNo'=>$data['item_no'],
                            'cardNo'=>base64_encode($cry->decrypt($v['code'])),
                            'sendTime'=>date('Y-m-d H:i:s'),
                            'username'=>$data['username'],
                            'orderSn'=>$data['ordersn'],
                            'address'=>$data['address'],
                            'phone'=>$data['phone'],
                            'shopName'=>$data['CardFName'],
                            'goodsName'=>$v['name'],
                            'msg'=>'短信发送成功',
                            'status'=>1
                        )
                    );
                    $orderArray=explode("~",$data['ordersn']);
                    Vendor('Util.MSSql');
                    $sqlser = new \MSSql();
                    $sqlser->Connect('192.168.1.10:1433','sa2','sasa','GT');
                    $mquery="insert into Import_Z0090(OrderId,DocEntry,Lineid,Phone,ExpNo,ExpCom)  values('".$orderArray[0]."','".$orderArray[1]."','".$orderArray[2]."','".$data['phone']."','".base64_encode($cry->decrypt($data['cardNo']))."','no shipping')";
                    $sqlser->query($mquery);
                    if(isset($data['phone']) && $this->_checkPhone($data['phone']))
                    {
                        $data['phone'] = rtrim($data['phone']);
                        $data['phone'] = ltrim($data['phone']);
                        $data['phone'] = substr( $data['phone'], 0,11);
                        if(strlen($v['password'])>0)
                        {
                            $msg="您好！这里是".$data['CardFName']."，您的".$v['name']."兑换码为：".$cry->decrypt($v['code'])."，密码：".base64_decode($v['password'])."，请妥善保管。如有任何问题请拨打：400-006-6830，祝您生活愉快！";
                        }else{
                            $msg="您好！这里是".$data['CardFName']."，您的".$v['name']."兑换码为：".$cry->decrypt($v['code'])."，请妥善保管。如有任何问题请拨打：400-006-6830，祝您生活愉快！";
                        }
                        echo $msg;
                        $this->_sendSMS($data['phone'], $msg);
                        if($res)
                        {

                        }
                    }else{

                        M('sap_virtualcard_api')->add(
                            array(

                                'itemNo'=>$data['item_no'],
                                'cardNo'=>base64_encode($cry->decrypt($v['code'])),
                                'sendTime'=>date('Y-m-d H:i:s'),
                                'username'=>$data['username'],
                                'orderSn'=>$data['ordersn'],
                                'address'=>$data['address'],
                                'phone'=>$data['phone'],
                                'shopName'=>$data['CardFName'],
                                'goodsName'=>$v['name'],
                                'msg'=>'手机号不正确',
                                'status'=>2
                                //'userId'=>$data['userId']
                            )
                        );
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
     * 调用发送短信接口
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

    // end -------- sap 订单 状态更新 发送短信 上海银行 广发银行

    /**
     * 自检测短信进程
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
     * @param string  $returnXML 是否返回xml格式 false返回数组
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

    private function asciitostr($sacii){
        $asc_arr= str_split(strtolower($sacii),2);
        $str='';
        for($i=0;$i<count($asc_arr);$i++){
            $str.=chr(hexdec($asc_arr[$i][1].$asc_arr[$i][0]));
        }
        return mb_convert_encoding($str,'UTF-8','GB2312');
    }

}