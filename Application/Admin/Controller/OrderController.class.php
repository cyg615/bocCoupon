<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: yangweijie <yangweijiester@gmail.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Admin\Controller;
class OrderController extends AdminController
{
    public function index()
    {
       // `orderId``app_id` `ctime`
        $orderId=I('orderId');
        $app_id=I('app_id');
        $name=I('name');
        $datetimeStart=I('datetimeStart');
        $datetimeEnd=I('datetimeEnd');
        $map=1;
        if($orderId)
        {
            $map.=" and o.orderId  like '%" . $orderId. "%'";
        }

        if(!empty($name)){
            $map.= " and g.name like '%" . $name . "%'";
        }
        if(!empty($app_id)){
            $map.= " and o.app_id like  '%" . $app_id . "%'";
        }

        if(!empty($datetimeStart)){
            $map.= " and o.ctime>='" . $datetimeStart. "'";
        }
        if(!empty($datetimeEnd)){
            $map.= " and o.ctime<='" . $datetimeEnd." 23:59:59". "'";
        }
        $nowPage    = empty(I('p'))? 1 : intval(I('p'));
        $nowPage    = $nowPage>0 ? $nowPage : 1;
        $table=M('order o');
        //$count= $table->join('left join vrc_venue_user AS u.id ON u = o.user_id')->join('left join vrc_venue_paylog AS p ON o.order_no = p.out_trade_no') ->where($where)->count();// 查询满足要求的总记录数
        //$count= $table->join('left join vrc_venue_user AS u ON u.id = o.user_id')->join('left join vrc_venues v on o.venues_id=v.id')->where($map)->count();// 查询满足要求的总记录数
        $count= $table->join('left join vrc_goods AS g  ON g.id = o.goods_id')->where($map)->count();// 查询满足要求的总记录数
        import("@.ORG.Util.AjaxPage");

        $Page =new \AjaxPage($nowPage,$count,'ajax-page',10);// 实例化分页类 传入总记录数、ajax更新的局部页面ID和每页显示的记录数(10)
        $Page->lastSuffix=false;
        $Page->setConfig('first','首页');
        $Page->setConfig('last','末页');
        $Page->setConfig('header','<span class="rows btn btn-default margin-l-2">共 %TOTAL_ROW% 条</span>');//分页条数
        $Page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');//分页样式：首页、末页等
        $show= $Page->show();// 分页显示输出
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $datas = $table->join('left join vrc_goods AS g  ON g.id = o.goods_id')->field('o.*,g.name')->where($map)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
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


}