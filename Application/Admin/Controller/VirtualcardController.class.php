<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: yangweijie <yangweijiester@gmail.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Admin\Controller;
class VirtualcardController extends AdminController
{
    public $baseApi = 'http://testcoupon.g-town.com.cn/';
    public function index()
    {
        Vendor('Crypt3Des.Crypt3Des');
        $Crypt3Des=\Crypt3Des::instance();
        $coupon=I('coupon');
        $order_id=I('order_id');
        $good_name=I('good_name');
        $cstatus=I('cstatus');
        $datetimeStart=I('datetimeStart');
        $datetimeEnd=I('datetimeEnd');
        $map=1;
        if($coupon)
        {
            //$map.=" and c.coupon ='" . $Crypt3Des->encrypt($coupon). "'";
            $map.=" and c.coupon ='" . base64_encode($coupon). "'";
        }
        if($order_id){
            $map.= " and c.order_id like '%" . $order_id . "%'";
        }
        if($good_name){
            $map.= " and g.name like '%" . $good_name . "%'";
        }
        if($cstatus!=0){
            $map.=" and c.cstatus =" . $cstatus . "";
        }
        if($datetimeStart){
            $map.= " and c.expire>=" . strtotime($datetimeStart). "";
        }
        if($datetimeEnd){
            $map.= " and c.expire<=" . strtotime($datetimeEnd." 23:59:59"). "";
        }
        $nowPage    = empty(I('p'))? 1 : intval(I('p'));
        $nowPage    = $nowPage>0 ? $nowPage : 1;
        $table= M("coupons c");
        $count= $table -> join('left join vrc_goods AS g ON c.goods_id = g.id') ->where($map)->count();// 查询满足要求的总记录数
        import("@.ORG.Util.AjaxPage");
        $Page =new \AjaxPage($nowPage,$count,'ajax-page',10);// 实例化分页类 传入总记录数、ajax更新的局部页面ID和每页显示的记录数(10)
        $Page->lastSuffix=false;
        $Page->setConfig('first','首页');
        $Page->setConfig('last','末页');
        $Page->setConfig('header','<span class="rows btn btn-default margin-l-2">共 %TOTAL_ROW% 条</span>');//分页条数
        $Page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');//分页样式：首页、末页等
        $show= $Page->show();// 分页显示输出
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $datas=$table ->join('left join vrc_goods AS g ON c.goods_id = g.id')->field('c.*,g.name')->where($map)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('datas',$datas);// 赋值数据集
        $this->assign('page',$show);// 赋值分页输出
        if(IS_AJAX){
            $this->display('index_ajax');
        }else{
            $this->display();
        }
    }
    public function import()
    {


        $this->meta_title = '卡券列表';
        //$admin_name=session('user_auth.username');
        $opmode = $_POST["opmode"];

        if ($opmode == "import") {
            $expire=strtotime($_POST['datetimeEnd']);
            $batch_no=$_POST['batchNo'];

            if(I('keyword')){
                $keyword = I('keyword');
                $map['sequence'] = array('eq',$keyword);
                $goods = M('goods')->where($map)->order('id desc')->field('id,sequence,stock')->find();
            }
            $File = D('File');
            $file_driver = C('DOWNLOAD_UPLOAD_DRIVER');
            $info = $File->upload($_FILES, C('DOWNLOAD_UPLOAD'), C('DOWNLOAD_UPLOAD_DRIVER'), C("UPLOAD_{$file_driver}_CONFIG"));
            $num=0;
            if (!$info) {
                $this->error($File->getError());
            } else {
                //取得成功上传的文件信息
                $model = M("coupons");
                $model->startTrans();
                Vendor('Excel.PHPExcel');
                Vendor('Crypt3Des.Crypt3Des');
                $Crypt3Des=\Crypt3Des::instance();
                $inputFileName = C('DOWNLOAD_UPLOAD.rootPath') . $info['uploadfile']["savepath"] . $info['uploadfile']["savename"];
                $objPHPExcel = \PHPExcel_IOFactory::load($inputFileName);
                $sheetData = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);

                for ($i = 2; $i <= count($sheetData); $i++) {
                    $data['coupon'] = !empty($sheetData[$i]["A"]) ? base64_encode($sheetData[$i]["A"]):"";
                    $data['password'] = !empty($sheetData[$i]["B"]) ? base64_encode($sheetData[$i]["B"]):"";
                    $data['expire']=strtotime(str_replace('/', '-',$this->coventdate($sheetData[$i]["C"])));
                    $data['goods_id']=$goods['id']>0 ? $goods['id'] :0;
                    $data['batch_no']=$batch_no;

                    if($model->add($data))
                    {
                        $num++;
                    }else{
                        //echo $model->getLastSql();
                        $model->rollback();
                        //echo 999;exit;
                        $this->error('导入失败，导入卡券失败！',U('/Admin/virtualcard/import'));
                    }


                }
                if (file_exists(__ROOT__ . "/" . $inputFileName)) {
                    unlink(__ROOT__ . "/" . $inputFileName);
                }
               if(!(M("goods")->where('id='.$goods['id'])->save(array('stock'=> $goods['stock']+$num)))) //更新导入成功的商品库存
               {
                   $model->rollback();
                   //$this->error('导入失败，更新库存失败！',U('/Admin/virtualcard/import'));
            }
                $insetArray=array(
                    'batch_no'=>$batch_no,
                    'num'=>$num,
                    'goods_id'=>$goods['id'],
                    //'expire'=>$expire,
                    'handle_name'=>session('user_auth.username'),
                    'add_time'=>time(),
                    'type'=>1
                );
                if(!M("coupons_log")->add($insetArray))
                {
                    $model->rollback();
                    $this->error('导入失败，批次号重复！',U('/Admin/virtualcard/import'));
                }
                $model->commit();
                $this->assign('jumpUrl', get_return_url());
                $this->success('导入成功！');
            }
        } else {
            $this->display();
        }
    }
    public function ajax_get_goods_name()
    {
        if(I('keyword')){
            $keyword = I('keyword');
            $map['name'] = array('like','%'.$keyword.'%');
        }
        $goods_name = M('goods')->where($map)->order('id desc')->field('name')->select();
        $this->ajaxReturn($goods_name);
    }
    /***转换excel默认月-日-年格式***/
     private function coventdate($data)
     {
         $gettime= explode('-',$data);
           if (checkdate($month=$gettime[0],$day=$gettime[1],$year=$gettime[2])){
            return date('Y-m-d',gmmktime(0,0,0,$month,$day,$year));
          }else{
              return $data;
           }

      }

}