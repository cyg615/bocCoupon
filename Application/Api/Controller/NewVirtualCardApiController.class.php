<?php

namespace Api\Controller;
use Think\Controller;
//use Vendor\Util\RedisClient;
use Home\Org\Util\CurlUtil;
//use Vendor\Util\Sqlserver;



/**
 * 虚拟卡相关数据接口 2017-07-31
 *
 */
class NewVirtualCardApiController  extends Controller{
    public $baseApi = 'http://101.132.131.63:88/';
    private $appid = 'sap';
    private $cry = null;
    function  index()
    {
       
        echo  "sap and sendcoupon client".PHP_EOL;
        exit;

    }
    function _initialize() {
        header('Content-Type:text/html;Charset=utf-8');
        Vendor('Util.Crypt3Des');
        Vendor('Util.MSSql');
        Vendor('Util.RedisClient');
        Vendor('Util.TService');
        $this->cry = \Crypt3Des::instance('admin123','sdfgrwqe');
    }
    
    
    /**
     * sap 调用触发
     */
    public function sapsend()
    {
        Vendor('Util.RedisClient');
        $redis =    \RedisClient::instance()->connect();
        $id = intval(I('id'));
        if(!$id) exit('Invalid Requesst ');
        //$sql="SELECT  T3.U_CardCode,T4.CardFName,T2.u_orderid+'~'+CONVERT(NVARCHAR(50),T2.docentry)+'~'+CONVERT(NVARCHAR(50),T2.lineid) ordersn,''shippingcode,''shippingname,T2.U_CSTNAME username,T2.U_address address,T2.u_zipcode zipcode,t2.u_phone phone,T2.U_OTPHONE tel,'' shippingfee,t2.U_STATE province,t2.u_city city,t2.U_County region,T0.u_itemcode item_no,t0.u_qty num FROM [@ti_z0131] t0 LEFT JOIN [@ti_z0130] T1 ON t0.docentry=t1.docentry LEFT JOIN [@ti_z0081] T2 ON t0.U_BsEntry=t2.docentry AND t0.u_bsline=t2.lineid left join [@ti_Z0080] T3 ON T2.docentry=t3.docentry left join ocrd T4 on t3.u_cardcode=t4.cardcode WHERE  t0.u_whscode='A02' AND T0.DocEntry=$id and  T3.U_CardCode in ('C20045')";
        $sql="SELECT  T0.DocEntry,T3.U_CardCode,T4.CardFName,T2.u_orderid+'~'+CONVERT(NVARCHAR(50),T2.docentry)+'~'+CONVERT(NVARCHAR(50),T2.lineid) ordersn,''shippingcode,''shippingname,T2.U_CSTNAME username,T2.U_address address,T2.u_zipcode zipcode,t2.u_phone phone,T2.U_OTPHONE tel,'' shippingfee,t2.U_STATE province,t2.u_city city,t2.U_County region,T0.u_itemcode item_no,t0.u_qty num FROM [@ti_z0131] t0 LEFT JOIN [@ti_z0130] T1 ON t0.docentry=t1.docentry LEFT JOIN [@ti_z0081] T2 ON t0.U_BsEntry=t2.docentry AND t0.u_bsline=t2.lineid left join [@ti_Z0080] T3 ON T2.docentry=t3.docentry left join ocrd T4 on t3.u_cardcode=t4.cardcode WHERE  t0.u_whscode='A02' AND T0.DocEntry={$id} and  T3.U_CardCode in (select cardcode from ocrd where QryGroup46='Y')";
        $sqlser = new \MSSql();
        $sqlser->Connect('192.168.1.10:1433','sa2','sasa','GT');
        $query=$sqlser->Query($sql);
        $cardList=$sqlser->GetRow($query);     
        if($cardList){
            $i = 0;
            foreach ($cardList as $cardinfo) {
                    if( $redis->lPush('new_sendvirtcard', json_encode($cardinfo))){
                        $i++;
                    }
            }
            echo 'success in '.$i."\n";
        }
        exit;
    }
    
    public  function testinmq(){
        $redis =    \RedisClient::instance()->connect();
        $cardList = [
            ['ordersn'=>'test'.time().rand(1000, 9999),'num'=>1,'item_no'=>'tx004','phone'=>'16602131212','username'=>'dem','address'=>'上海'],
        ];
            $i = 0;
            foreach ($cardList as $cardinfo) {
                if( $redis->lPush('new_sendvirtcard', json_encode($cardinfo))){
                    $i++;
                }
            }
            echo 'success in '.$i."\n";
        exit;
    }
  
    /**
     * 卡券购买
     * @param unknown $cardinfo
     * @return boolean|unknown
     */
    function buycoupon($cardinfo){
        $ordercheck = M('sap_virtualcard_api')->where(['orderSn'=>$cardinfo['ordersn']])->select();
        if($ordercheck){
            error_log("订单号 :{$cardinfo['ordersn']} 重复,已忽略");
            return false;
        }
     
            $inteface = 'coupon/buy';
            $data = array('goodsSQ' => $cardinfo['item_no'], 'appId' => $this->appid, 'amount' => intval($cardinfo['num']));
            $sign = $this->createSign($this->appid, $data);
            $data['sign'] = $sign;
            $res = \TService::instance()->requestService($this->baseApi . $inteface, $data);
            if ($res['httpCode'] == 200) {
                $coupon = $res['data']['data'];
                if (!isset($coupon['orderId']) || $coupon['orderId'] == '') {
                    //购买卡券失败
                    $this->addlog($cardinfo, 2,  $coupon['msg']);
                    return false;
                } else {
                    $inteface = 'coupon/querycoupon';
                    $data = array('appId' =>$this->appid, 'orderNo' => $coupon['orderId'], 'page' => 1);
                    $sign = $this->createSign($this->appid, $data);
                    $data['sign'] = $sign;
                    $res = \TService::instance()->requestService($this->baseApi.$inteface, $data);
                    if ($res['httpCode'] != 200) {
                        return false ;
                    }
                    $cardinfo['couponList'] = $res['data']['couponList'];
                    foreach (  $cardinfo['couponList']  as $cp){
                        $cardinfo['cardName'] = $cp['name'];
                        $cardinfo['cardNo']= base64_encode($this->cry->decrypt($cp['code']));
                        $this->addlog($cardinfo, 0, 'buy coupon success');
                    }
                    return $cardinfo;
        
                }
            } else {
             $this->addlog($cardinfo, 2, '请求失败');
             return false;
            }
        
        
        return false;
    }
    


    /**
     * 定时任务执行 发送卡券
     */
    function sendTask(){
        ini_set('default_socket_timeout', -1);
        set_time_limit(0);
    
        $redis =    \RedisClient::instance()->connect();

        $i=$redis->lSize('new_sendvirtcard');
        echo  'size '.$i."\n";
        while ($i){
            $data = $redis->lPop('new_sendvirtcard');
          
            $i--;
            $order = json_decode($data,true);
            if(!$order){
                continue;
            }
            $buy_res = $this->buycoupon($order);
            if($buy_res){
                foreach ($buy_res['couponList'] as $coupon){
                    $passinfo = '';
                    if(strlen($coupon['password'])>0)
                    {
                        $passinfo = '密码:'.base64_decode($coupon['password']).',';
                    }
                    $couponCode = $this->cry->decrypt($coupon['code']);
                    $msg='尊敬的浦发客户，感谢参加“纪念日存单”活动，您已获赠爱奇艺黄金会员VIP月卡一张，券码为：'.$couponCode.','.$passinfo." 有效期至 ".$coupon['expire'].".使用问题请致电爱奇艺客服400-923-7171。";
                    $sendres = json_decode($this->_sendSMS($buy_res['phone'], $msg),true);
                   // $sendres = json_decode('{"ret":200,"data":{"code":"0","msg":"发送成功"},"msg":""}',true);
                    $save = ['status'=>0,'msg'=>$sendres['data']['msg'],'sendTime'=>date('Y-m-d H:i:s')];
                    if($sendres['ret']==200&&$sendres['data']['code']==0){
                        $save['status'] =1 ;
                    }
                    $this->updateSmsStatus(base64_encode($couponCode),$save);
                }
              
            }

        }
        exit('SUCCESS');

    }
    

    /**
     * 卡券下单记录
     * @param unknown $cardinfo
     * @param unknown $status
     * @param unknown $msg
     */
    private  function addlog($cardinfo,$status,$msg){
       
        $save =  [
            'itemNo' => $cardinfo['item_no'],
            'cardNo' => isset($cardinfo['cardNo'])?$cardinfo['cardNo']:'',
            'username' => $cardinfo['username'],
            'orderSn' => $cardinfo['ordersn'],
            'address' => $cardinfo['address'],
            'phone' => $cardinfo['phone'],
            'shopName' => isset($cardinfo['CardFName'])?$cardinfo['CardFName']:'',
            'goodsName' => isset($cardinfo['cardName']) ? $cardinfo['cardName'] : '',
            'msg' => $msg,
            'status' => $status
        ];
        return   M('sap_virtualcard_api')->add($save );
    }
    
    
    /**
     * 检查手机号码
     * @param unknown $phone
     */
    private function _checkPhone($phone){
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
    
    
  /**
   * 根据卡券号  修改记录
   * @param unknown $couponCode
   * @param unknown $save
   */
    private function updateSmsStatus($couponCode,$save=[]){
        $res =    M('sap_virtualcard_api')->where(['cardNo'=>$couponCode])->save($save);
        return $res;
    }
    
    /**
     * 回传sap 定时任务轮询
     */
    public function callbackSap(){
        $sqlser = new \MSSql();
        $sqlser->Connect('192.168.1.10:1433','sa2','sasa','GT');
        $list =  M('sap_virtualcard_api')->where(['sapStatus'=>0])->select();
        foreach ($list as $vo){
            $orderArray = explode('~', $vo['orderSn']);
            $mquery="insert into Import_Z0090(OrderId,DocEntry,Lineid,Phone,ExpNo,ExpCom)  values('".$orderArray[0]."','".$orderArray[1]."','".$orderArray[2]."','".$vo['phone']."','".base64_encode($this->cry->decrypt($vo['cardNo']))."','no shipping')";
            if($sqlser->query($mquery)){
                $this->updateSmsStatus($vo['cardNo'],['sapStatus'=>1]);
            };
        }
      
        echo 'Success '.count($list)."\n";
        error_log(date("Y-m-d H:i:s")."call sqp order");
      
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
            'sysCode' =>'couponApi'
        );
        return  http($api, $params,'POST');
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
        $header[]="Content-Type: text/xml; charset=utf-8";
        $header[]="Subject:{$subject}";
        $header[]="Message-From:{$from}";
        $header[]="SignKey:{$sign}";
        $header[]="Content-Length: ".strlen($xml);
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