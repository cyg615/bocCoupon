<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: yangweijie <yangweijiester@gmail.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Admin\Controller;
class LogController extends AdminController
{
    public function index()
    {
        $ip_address=I('ip_address');
        $status=I('status');
        $keyworld=I('keyworld');
        $datetimeStart=I('datetimeStart');
        $datetimeEnd=I('datetimeEnd');
        $map=" 1";
        if($ip_address)
        {
            $map.= " and host =" . $ip_address . "";
        }
        if($status){
            $map.= " and status =" . $status . "";
        }
        if($keyworld){
            $map.= " and (msg like '%" . $keyworld . "%' or task like '%".$keyworld."%')";
        }
        if($datetimeStart){
            $map.= " and start_time>=" . strtotime($datetimeStart). "";
        }
        if($datetimeEnd){
            $map.= " and start_time<=" . strtotime($datetimeEnd." 23:59:59"). "";
        }
        $nowPage    = empty(I('p'))? 1 : intval(I('p'));
        $nowPage    = $nowPage>0 ? $nowPage : 1;
        $table=M('msg_info');
        $count= $table->where($map)->count();// 查询满足要求的总记录数
        import("@.ORG.Util.AjaxPage");
        $Page =new \AjaxPage($nowPage,$count,'ajax-page',10);// 实例化分页类 传入总记录数、ajax更新的局部页面ID和每页显示的记录数(10)
        $Page->lastSuffix=false;
        $Page->setConfig('first','首页');
        $Page->setConfig('last','末页');
        $Page->setConfig('header','<span class="rows btn btn-default margin-l-2">共 %TOTAL_ROW% 条</span>');//分页条数
        $Page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');//分页样式：首页、末页等
        $show= $Page->show();// 分页显示输出
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $datas = $table->where($map)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        //echo $table->getLastSql();exit;
        $this->assign('datas',$datas);// 赋值数据集
        $this->assign('page',$show);// 赋值分页输出
        if(IS_AJAX){
            $this->display('index_ajax');
        }else{
            $this->display();
        }
    }


}