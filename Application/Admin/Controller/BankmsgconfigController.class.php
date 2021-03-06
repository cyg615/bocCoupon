<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: yangweijie <yangweijiester@gmail.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Admin\Controller;
class BankmsgconfigController extends AdminController
{
    /***商品列表***/
    public function index()
    {
        $bank_name = trim(I('bank_name'));
        $bank_sap_code=trim(I('bank_sap_code'));
        $enable=trim(I('enable'));
        $nowPage    = empty(I('p'))? 1 : intval(I('p'));
        $nowPage    = $nowPage>0 ? $nowPage : 1;
        $map=1;
        if(!empty($bank_name))
        {
            $map.=" and bank_name  like '%" . $bank_name. "%'";
        }
        if(!empty($bank_sap_code)){
            $map.= " and bank_sap_code like '%" . $bank_sap_code . "%'";
        }

        if(isset($enable) && $enable!=''){
            $map.= " and enable=" . $enable. "";
        }
        $table=M('msg_config');
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
        $this->assign('datas',$datas);// 赋值数据集
        $this->assign('page',$show);// 赋值分页输出
        if(IS_AJAX){
            $this->display('index_ajax');
        }else{
            $this->display();
        }
    }
    /***添加商品***/
    public function edit()
    {
        $id = I('id');
        if ($id > 0){
            $list = M("msg_config")->where('id='.$id)->find();

        //print_r($goods);exit;
        $this->assign("list", $list);
       }
       //print_r($goods);exit;
        $this->display();

    }
    public function editing()
    {
        $id=I('id');
        $model = M("msg_config");
        $data=array(
            'bank_name'=>trim(I('bank_name')),
            'bank_sap_code'=>trim(I('bank_sap_code')),
            'enable'=>I('enable'),

        );
        if($id)
        {
            //$this->meta_title = '更新商品';
            $condition='id='.$id;
            $model->where($condition)->save($data);
            $this->success('更新成功！',U('/Admin/bankmsgconfig/index'));
        }else{
            //$this->meta_title = '添加商品';
            $data['ctime']=date("Y-m-d H;i:s");
            $model->add($data);
            $this->success('添加成功！',U('/Admin/bankmsgconfig/index'));
            //$this->success('成功',U('分组/模块/方法',array('id'=>$last_id)));
        }

    }


}