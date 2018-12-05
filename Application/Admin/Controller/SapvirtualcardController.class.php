<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: yangweijie <yangweijiester@gmail.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Admin\Controller;
class SapvirtualcardController extends AdminController
{
    public $baseApi = 'http://testcoupon.g-town.com.cn/';
    public function index()
    {
        // `orderId``app_id` `ctime`
        $orderSn=trim(I('orderSn'));
        $username=trim(I('username'));
        $goodsName=trim(I('goodsName'));
        $address=trim(I('address'));
        $phone=trim(I('phone'));
        $datetimeStart=I('datetimeStart');
        $datetimeEnd=I('datetimeEnd');
        $shopName=trim(I('shopName'));
        $cardNo=trim(I('cardNo'));
        $status=trim(I('status'));
        $map=1;
        if($orderSn)
        {
            $map.=" and s.orderSn  like '%" . $orderSn. "%'";
        }

        if(!empty($username)){
            $map.= " and s.username like '%" . $username . "%'";
        }

        if(!empty($shopName)){
            $map.= " and s.shopName like '%" . $shopName . "%'";
        }

        if(!empty($goodsName)){
            $map.= " and s.goodsName like  '%" . $goodsName . "%'";
        }

        if(!empty($address)){
            $map.= " and s.address like  '%" . $address . "%'";
        }

        if(!empty($phone)){
            $map.= " and s.phone like  '%" . $phone . "%'";
        }

        if(!empty($datetimeStart)){
            $map.= " and s.sendTime>='" . $datetimeStart. "'";
        }
        if(!empty($datetimeEnd)){
            $map.= " and s.sendTime<='" . $datetimeEnd." 23:59:59". "'";
        }
        if(!empty($cardNo))
        {
            $map.= " and s.cardNo like  '%" . base64_encode($cardNo) . "%'";
        }
        if(!empty($status))
        {
            $map.=" and s.status =" . $status . "";
        }
        $nowPage    = empty(I('p'))? 1 : intval(I('p'));
        $nowPage    = $nowPage>0 ? $nowPage : 1;
        $table=M('sap_virtualcard_api s');
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
        $datas = $table->where($map)->order('s.id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
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

    public function edit()
    {
        $id = I('id');
        if ($id > 0){
            $list = M("sap_virtualcard_api")->where('id='.$id)->find();

            //print_r($goods);exit;
            $this->assign("list", $list);
        }
        //print_r($goods);exit;
        $this->display();

    }
    public function editing()
    {
        $id=I('id');
        $model = M("sap_virtualcard_api");
        $data=array(
            'orderSn'=>trim(I('orderSn')),
            'itemNo'=>trim(I('itemNo')),
            'cardNo'=>$this->is_base64(trim(I('cardNo'))) ? trim(I('cardNo')) : base64_encode(trim(I('cardNo'))) ,
            'username'=>trim(I('username')),
            'address'=>trim(I('address')),
            'shopName'=>trim(I('shopName')),
            'goodsName'=>trim(I('goodsName')),
            'status'=>trim(I('status'))

        );
        if($id)
        {
            //$this->meta_title = '更新商品';
            $condition='id='.$id;
            $model->where($condition)->save($data);
            $this->success('更新成功！',U('/Admin/sapvirtualcard/index'));
        }else{
            //$this->meta_title = '添加商品';
            $data['ctime']=date("Y-m-d H;i:s");
            $model->add($data);
            $this->success('添加成功！',U('/Admin/sapvirtualcard/index'));
            //$this->success('成功',U('分组/模块/方法',array('id'=>$last_id)));
        }

    }
    private  function is_base64($str){
        if($str==base64_encode(base64_decode($str))){
            return true;
        }else{
            return false;
        }
    }

}