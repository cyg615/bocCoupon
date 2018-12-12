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
        $res=array('stat'=>'00','result'=>'退货成功');
        $orderId = trim(I('orderId'));
        $waresId=trim(I('waresId'));
        $wInfo=trim(I('wInfo'));
        $returnDate=trim(I('returnDate'));
        $returnTime=trim(I('returnTime'));
        $model = M("boc_order");
        $model->where('coupon_sn='.base64_encode($wInfo))->save(array('status'=>2,'out_order_sn'=>$orderId,'return_time'=>date('Y-m-d', strtotime(trim($returnDate)))." ".trim($returnTime)));
        $res['date']=date("Y-m-d H:i:s");
        echo json_encode($res);exit;
    }
}