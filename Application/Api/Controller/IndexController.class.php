<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Api\Controller;
use Think\Controller;
//use Vendor\Util\Mysql;

/*
 *
 * 中行卡券实时接口
 *
 */
class IndexController extends Controller {
    /*
     * 退货信息接口
     */
    public function index(){
        //ob_start('ob_gzhandler'); // 开启gzip,屏蔽则关闭
         header('content-type:application/json');
         header('Content-Encoding: identity');
        //http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        \Think\Log::write('请求Url：'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'],'INFO');
        $res=array('stat'=>00,'result'=>'');
        $bocconfig=C('BOC_COUPON_CONFIG');
        $bocconfig['upload']=DOC_ROOT.$bocconfig['upload'];
        Vendor('Util.Mysql');
        $couponApidb = new \Mysql('101.132.131.63','gtown','admin123','couponApi');
        //$data=trim(I('data'));
        $data=trim($_GET['data']);
//        \Think\Log::write('server：'.json_encode($_SERVER));
//        \Think\Log::write('server：'.json_encode($_SERVER));
//        \Think\Log::write('input：'.file_get_contents('php://input'));
//        \Think\Log::write('get data：'.$_GET['data']);
//        \Think\Log::write('请求参数：'. $data);

        if (PATH_SEPARATOR==':') {
            $task="cd ". DOC_ROOT . "/Uploads/Boccoupon/Library/RSA/" . ";java -jar RsaDecryUtil.jar ".   DOC_ROOT . "/Uploads/Boccoupon/Library/RSA/cert/pri.der ".$data;
        } else {
            $task = substr(DOC_ROOT, 0, 2) . "&cd " . DOC_ROOT . "/Uploads/Boccoupon/Library/RSA/" . "&java -jar RsaDecryUtil.jar "  . DOC_ROOT . "/Uploads/Boccoupon/Library/RSA/cert/pri.der ".$data;
        }
        exec( $task." 2>&1",$out);//
        \Think\Log::write('解密参数：'.json_encode($out),'INFO');
        if(!$this->is_json($out[0]))
        {
            $res['stat']=99;
            $res['result']='The return failed and the data were wrong';
        }else{
            $result=json_decode($out[0],true);
            if($result['status']!=200)
            {
                $res['stat']=99;
                $res['result']='Failure of return, failure to decrypt data';
            }else{
                $result['response']=stripslashes(trim($result['response'],'"'));
                $parms=json_decode($result['response'],true);
                $model = M("boc_order");
                $order= $model->where("coupon_sn='".$this->urlsafe_b64encode($parms['wInfo'])."' ")->find();
                if(empty($order))
                {
                    $res['stat']=99;
                    $res['result']='Return failure, orders do not exist';
                }else{
                     $url="http://101.132.131.63:88/coupon/couponinfo?code=".$parms['wInfo'];
                     $rep=$this->getHttpContent($url, $method = 'GET', array());
                    //print_r($res);
                    if(isset($rep["data"]["status"]))
                    {
                        if($rep["data"]["status"]!=4)
                        {
                            $res['stat']='00';
                            $res['result']='Return success';
                            $model->where("coupon_sn='".base64_encode($parms['wInfo'])."'")->save(array('status'=>2,'out_order_sn'=>$parms['orderId'],'lastupdate_time'=>date('Y-m-d', strtotime(trim($parms['returnDate'])))." ".trim($parms['returnTime'])));
                            M('goods')->where("`id` ='".$order['goods_id'] ."'")->setInc('stock',1);
                            $upsql="UPDATE  `couponApi`  SET `cstatus`=-1  WHERE  coupon='".$this->urlsafe_b64encode($parms['wInfo'])."' ";
                            $couponApidb->query($upsql);
                        }else{
                            $res['stat']=99;
                            $res['result']='Card voucher has been used';
                        }
                    }else{
                        $res['stat']=99;
                        $res['result']='data error';
                    }

                }
            }
        }
        $res['date']=date("Y-m-d H:i:s");
        \Think\Log::write('响应参数：'.json_encode($res),'INFO');
        ob_end_clean();
        echo json_encode($res);exit;
    }
    /*
   * 卡券已使用更新接口
   */
    public function coupon_use_info()
    {

        $start_date=date("Y-m-d",strtotime("-3 day"));
        $end_date=date("Y-m-d",time());
        $model = M("boc_order o");
        $coupon_sn=trim(I('coupon_sn'));
        //$datas = $model->field('o.*')->where("o.status =1 and o.use_time>=".$start_date." and o.use_time<'".$end_date."'")->order('o.id desc')->select();
        $datas = $model->field('o.*')->where("o.status =1 and o.id=65")->order('o.id desc')->select();
        for($i=0;$i<count($datas);$i++)
        {
            $data=array(
                "waresId"=> $datas[$i]['goods_code'],
                "wEid"=>$datas[$i]['stock_code'],
                "wSign"=>"E",
                "wInfo"=>base64_decode($datas[$i]['coupon_sn']),
                "usedDate"=>date("Ymd",strtotime($datas[$i]['use_time'])),
                "UsedTime"=>date("H:i:s",strtotime($datas[$i]['use_time']))
                //https://101.231.206.213/Coupons/couponUsed.do
            );
            $json_data='"'.str_replace('"','\"',json_encode($data)).'"';
            //echo  $json_data."<br>";
            if (PATH_SEPARATOR==':') {
                $task="cd ". DOC_ROOT . "/Uploads/Boccoupon/Library/RSA/" . ";java -jar RsaEncryUtil.jar ".   DOC_ROOT . "/Uploads/Boccoupon/Library/RSA/cert/bocpub.der ". $json_data;
            } else {
               $task = substr(DOC_ROOT, 0, 2) . "&cd " . DOC_ROOT . "/Uploads/Boccoupon/Library/RSA/" . "&java -jar RsaEncryUtil.jar "  . DOC_ROOT . "/Uploads/Boccoupon/Library/RSA/cert/bocpub.der ". $json_data;
            }
           exec( $task." 2>&1",$output);//
            //echo $task."<br>";
            //print_r($output);
            if($this->is_json($output[0]))
            {
                $outresult=json_decode($output[0],true);
                if($outresult['status']==200)
                {
                    $res= $outresult['response'];
                    $url='https://101.231.206.213/Coupons/couponUsed.do?data='.$res;
                    $res=$this->getHttpContent($url, $method = 'GET', array());
                   //print_r($res);
                    if($res["stat"]=="00")
                    {
                        //echo "testok";

                    }else{

                    }
                }
            }
           unset($output);
        }

    }

/*
 * 卡券退货信息更新接口
 */
    public function coupon_refund()
    {
        $start_date=date("Y-m-d",strtotime("-3 day"));
        $end_date=date("Y-m-d",time());
        $model = M("boc_order o");
        $datas = $model->field('o.*')->where("o.status =2 and o.use_time>=".$start_date." and o.lastupdate_time<'".$end_date."'")->order('o.id desc')->select();
        //echo $model->getLastSql();
        for($i=0;$i<count($datas);$i++)
        {
            $data=array(
                "waresId"=> $datas[$i]['goods_code'],
                "wEid"=>$datas[$i]['stock_code'],
                "orderId"=>$datas[$i]['out_order_sn'],
                "wInfo"=>base64_decode($datas[$i]['coupon_sn']),
                "flag"=>"Y"
            );
            $json_data='"'.str_replace('"','\"',json_encode($data)).'"';
            if (PATH_SEPARATOR==':') {
                $task="cd ". DOC_ROOT . "/Uploads/Boccoupon/Library/RSA/" . ";java -jar RsaEncryUtil.jar ".   DOC_ROOT . "/Uploads/Boccoupon/Library/RSA/cert/bocpub.der ".$json_data;
            } else {
                $task = substr(DOC_ROOT, 0, 2) . "&cd " . DOC_ROOT . "/Uploads/Boccoupon/Library/RSA/" . "&java -jar RsaEncryUtil.jar "  . DOC_ROOT . "/Uploads/Boccoupon/Library/RSA/cert/bocpub.der ".$json_data;
            }
            exec( $task." 2>&1",$output);//
            //echo $task."<br>";
            //print_r($output);
            if($this->is_json($output[0]))
            {

                $outresult=json_decode($output[0],true);
                if($outresult['status']==200)
                {
                    //echo "8888<br>";
                    $res= $outresult['response'];
                    $url="https://101.231.206.213/Coupons/couponRefund.do?data=".$res;
                    //echo $url."<br>";
                    //print_r($data);
                    $res=$this->getHttpContent($url, $method = 'GET', array());
                    //print_r($res);
                    if($res["stat"]=="00")
                    {

                    }else{

                    }
                }
            }
            unset($output);
        }
    }

    function urlsafe_b64encode($string) {
        $data = base64_encode($string);
        $data = str_replace(array('+','/','='),array('-','_',''),$data);
        return $data;
    }

/*
 * 判断是否json的函数
 */
    private  function is_json($string) {
       json_decode($string);
       return (json_last_error() == JSON_ERROR_NONE);
    }
    /**
     *
     * @param        $url
     * @param string $method
     * @param array  $postData
     *
     * @return mixed|null|string
     */
private function getHttpContent($url, $method = 'GET', $postData = array())
    {
        $data = '';
        if (!empty($url)) {
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30); //30秒超时
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                //curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_jar);
                if (strtoupper($method) == 'POST') {
                    $curlPost = is_array($postData) ? http_build_query($postData) : $postData;
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
                }
                $data = curl_exec($ch);
                curl_close($ch);
            } catch (Exception $e) {
                $data = null;
            }
        }
        return json_decode($data,true);
    }
}