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

/**
 * 前台首页控制器
 * 主要获取首页聚合数据
 */
class BocController extends Controller {

	//系统首页
    public function returnapi(){
        \Think\Log::write('请求Url：'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'],'INFO');
        $res=array('stat'=>'00','result'=>'success');
        $orderId = trim(I('orderId'));
        $waresId=trim(I('waresId'));
        $wInfo=trim(I('wInfo'));
        $returnDate=trim(I('returnDate'));
        $returnTime=trim(I('returnTime'));
        \Think\Log::write('券码：'.$wInfo);
        $orderInfo=M("boc_order")->where("coupon_sn='".$this->urlsafe_b64encode(trim($wInfo))."' ")->find();
        if(empty($orderInfo))
        {

            $res=array('stat'=>'99','result'=>'Order does not exist');
            echo json_encode($res);exit;
        }
        if($orderInfo['use_status']==1)
        {
            $res=array('stat'=>'99','result'=>'coupon is used');
            echo json_encode($res);exit;
        }

        $model = M("boc_order");
        $model->where('coupon_sn='.$this->urlsafe_b64encode($wInfo))->save(array('status'=>2,'out_order_sn'=>$orderId,'return_time'=>date('Y-m-d', strtotime(trim($returnDate)))." ".trim($returnTime)));
        $res['date']=date("Y-m-d H:i:s");
        \Think\Log::write('响应参数：'.json_encode($res),'INFO');
        echo json_encode($res);exit;
    }

    private  function urlsafe_b64encode($string) {
        $data = base64_encode($string);
        \Think\Log::write('卡券$string：'. $data,'INFO');
        $data = str_replace(array('+','/','='),array('-','_',''),$data);
        return $data;
    }
}