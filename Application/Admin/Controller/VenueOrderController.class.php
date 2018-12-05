<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: yangweijie <yangweijiester@gmail.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Admin\Controller;
class VenueOrderController extends AdminController
{
    public function index()
   {
           $order_no=I('order_no');
           $three_order_no=I('three_order_no');
           $nickname=I('nickname');
           $user_mobile=trim(I('user_mobile'));
           $pay_channel=trim(I('pay_channel'));
           $venues_name=I('venues_name');
           $order_status=I('order_status');
           $datetimeStart=I('datetimeStart');
           $datetimeEnd=I('datetimeEnd');
           $map=1;
           if($order_no)
           {
               $map.=" and o.order_no  like '%" . $order_no. "%'";
           }
           if(!empty($three_order_no)){
               $map.= " and o.three_order_no like '%" . $three_order_no . "%'";
           }
           if(!empty($nickname)){
               $map.= " and u.nickname like '%" . $nickname . "%'";
           }
          if(!empty($user_mobile)){
              $map.= " and o.user_mobile like '%" . $user_mobile . "%'";
           }
           if(!empty($venues_name)){
               $map.=" and o.venues_name like '%" . $venues_name . "%'";
           }
          if(isset($order_status) && $order_status!=''){
               $map.= " and o.order_status=" . $order_status. "";
           }
           if(!empty($datetimeStart)){
               $map.= " and o.ctime>='" . $datetimeStart. "'";
           }
           if(!empty($datetimeEnd)){
               $map.= " and o.ctime<='" . $datetimeEnd." 23:59:59". "'";
           }
           if(!empty($pay_channel)){
               $map.= " and o.pay_channel  like '%" . $pay_channel. "%'";
           }
            $nowPage    = empty(I('p'))? 1 : intval(I('p'));
            $nowPage    = $nowPage>0 ? $nowPage : 1;
            $table=M('venue_order o');
            //$count= $table->join('left join vrc_venue_user AS u.id ON u = o.user_id')->join('left join vrc_venue_paylog AS p ON o.order_no = p.out_trade_no') ->where($where)->count();// 查询满足要求的总记录数
            $count= $table->join('left join vrc_venue_user AS u ON u.id = o.user_id')->join('left join vrc_venues v on o.venues_id=v.id')->where($map)->count();// 查询满足要求的总记录数
            import("@.ORG.Util.AjaxPage");

            $Page =new \AjaxPage($nowPage,$count,'ajax-page',10);// 实例化分页类 传入总记录数、ajax更新的局部页面ID和每页显示的记录数(10)
            $Page->lastSuffix=false;
            $Page->setConfig('first','首页');
            $Page->setConfig('last','末页');
            $Page->setConfig('header','<span class="rows btn btn-default margin-l-2">共 %TOTAL_ROW% 条</span>');//分页条数
            $Page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');//分页样式：首页、末页等
            $show= $Page->show();// 分页显示输出
            // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
            $datas = $table->join('left join vrc_venue_user AS u ON u.id = o.user_id')->join('left join vrc_venues v on o.venues_id=v.id')->field('o.*,u.nickname,v.venues_name as venuesName')->where($map)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
            //echo $table->getLastSql();exit;
            $this->assign('datas',$datas);// 赋值数据集
            $this->assign('page',$show);// 赋值分页输出
           //print_r($show); exit;
            if(IS_AJAX){
                $this->display('index_ajax');
            }else{
                $this->display();
            }
    }
    public function orderInfo()
    {

    }
    public function  payList()
    {
        $order_no=trim(I('order_no'));
        $three_order_no=trim(I('three_order_no'));
        $nickname=trim(I('nickname'));
        $transaction_id=trim(I('transaction_id'));
        $datetimeStart=trim(I('datetimeStart'));
        $datetimeEnd=trim(I('datetimeEnd'));
        $map=1;
        if(!empty($order_no))
        {
            $map.=" and p.out_trade_no  like '%" . $order_no. "%'";
        }
        if(!empty($three_order_no)){
            $map.= " and o.three_order_no like '%" . $three_order_no . "%'";
        }
        if(!empty($nickname)){
            $map.= " and u.nickname '%" . $nickname . "%'";
        }
        if(!empty($transaction_id)){
            $map.= " and p.transaction_id like '%" . $transaction_id. "%'";
        }
        if(!empty($datetimeStart)){
            $map.= " and p.ctime>='" . $datetimeStart. "'";
        }
        if(!empty($datetimeEnd)){
            $map.= " and p.ctime<='" . $datetimeEnd." 23:59:59". "'";
        }
        $nowPage    = empty(I('p'))? 1 : intval(I('p'));
        $nowPage    = $nowPage>0 ? $nowPage : 1;
        $table=M('venue_paylog p');
        $count= $table->join('left join vrc_venue_user AS u ON u.openid = p.openid')->join('left join vrc_venue_order AS o ON o.order_no = p.out_trade_no')->where($map)->count();// 查询满足要求的总记录数
        import("@.ORG.Util.AjaxPage");
        $Page =new \AjaxPage($nowPage,$count,'ajax-page',10);// 实例化分页类 传入总记录数、ajax更新的局部页面ID和每页显示的记录数(10)
        $Page->lastSuffix=false;
        $Page->setConfig('first','首页');
        $Page->setConfig('last','末页');
        $Page->setConfig('header','<span class="rows btn btn-default margin-l-2">共 %TOTAL_ROW% 条</span>');//分页条数
        $Page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');//分页样式：首页、末页等
        $show= $Page->show();// 分页显示输出
        $datas = $table->join('left join vrc_venue_user AS u ON u.openid = p.openid')->join('left join vrc_venue_order AS o ON o.order_no = p.out_trade_no')->field('p.*,u.nickname,o.total_fee as amount,o.three_order_no')->where($map)->order('p.id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        //echo $table->getLastSql();exit;
        $this->assign('datas',$datas);// 赋值数据集
        $this->assign('page',$show);// 赋值分页输出
        if(IS_AJAX){
            $this->display('payList_ajax');
        }else{
            $this->display('payList');
        }

    }



}