<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: yangweijie <yangweijiester@gmail.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Admin\Controller;
class VenueUserController extends AdminController
{
public function index()
{
    $where=array();
    $name = trim(I('name'));
    $phone=trim(I('phone'));
    $nowPage    = empty(I('p'))? 1 : intval(I('p'));
    $nowPage    = $nowPage>0 ? $nowPage : 1;
    if(!empty($name)){
    $where['nickname']= array('like','%'.(string)$name.'%');
    }
    if(!empty($phone)){
        $where['phone']= array('like','%'.$phone.'%');
    }
    $table=M('venue_user');
    $count= $table->where($where)->count();// 查询满足要求的总记录数
    import("@.ORG.Util.AjaxPage");
    $Page =new \AjaxPage($nowPage,$count,'ajax-page',10);// 实例化分页类 传入总记录数、ajax更新的局部页面ID和每页显示的记录数(10)
    $Page->lastSuffix=false;
    $Page->setConfig('first','首页');
    $Page->setConfig('last','末页');
    $Page->setConfig('header','<span class="rows btn btn-default margin-l-2">共 %TOTAL_ROW% 条</span>');//分页条数
    $Page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');//分页样式：首页、末页等
    $show= $Page->show();// 分页显示输出
    // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
    $datas = $table->where($where)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
    $this->assign('datas',$datas);// 赋值数据集
    $this->assign('page',$show);// 赋值分页输出
    if(IS_AJAX){
        $this->display('index_ajax');
    }else{
        $this->display();
    }
    }




}