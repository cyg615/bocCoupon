<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: yangweijie <yangweijiester@gmail.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Admin\Controller;
use Think\Exception;
use ZipArchive;
class BocvirtualcardController extends AdminController
{
    //public $baseApi = 'http://39.104.103.212:88/';//测试
    public $baseApi ='http://101.132.131.63:88/';//正式
    public $productbaseApi ='http://47.93.238.180:9001/';//正式


    public function index()
    {
        Vendor('Crypt3Des.Crypt3Des');
        $Crypt3Des=\Crypt3Des::instance();
        $coupon_sn=I('coupon_sn');
        $inner_order_sn=I('inner_order_sn');
        $out_order_sn=I('out_order_sn');
        $status=I('status');
        $datetimeStart=I('datetimeStart');
        $datetimeEnd=I('datetimeEnd');
        $map=1;
        if($coupon_sn)
        {
            $map.=" and o.coupon_sn ='" . base64_encode($coupon_sn). "'";
        }
        if($inner_order_sn){
            $map.= " and o.inner_order_sn like '%" . $inner_order_sn . "%'";
        }
        if($out_order_sn){
            $map.= " and o.out_order_sn '%" . $out_order_sn . "%'";
        }
        if(isset($status) && $status!=''){
            $map.=" and o.status =" . $status . "";
        }
        if($datetimeStart){
            $map.= " and o.sale_time>='" . $datetimeStart. "'";
        }
        if($datetimeEnd){
            $map.= " and o.sale_time<='" . $datetimeEnd." 23:59:59". "'";
        }
        $nowPage    = empty(I('p'))? 1 : intval(I('p'));
        $nowPage    = $nowPage>0 ? $nowPage : 1;
        $table= M("boc_order o");
        $count= $table ->where($map)->count();// 查询满足要求的总记录数
        import("@.ORG.Util.AjaxPage");
        $Page =new \AjaxPage($nowPage,$count,'ajax-page',10);// 实例化分页类 传入总记录数、ajax更新的局部页面ID和每页显示的记录数(10)
        $Page->lastSuffix=false;
        $Page->setConfig('first','首页');
        $Page->setConfig('last','末页');
        $Page->setConfig('header','<span class="rows btn btn-default margin-l-2">共 %TOTAL_ROW% 条</span>');//分页条数
        $Page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');//分页样式：首页、末页等
        $show= $Page->show();// 分页显示输出
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $datas=$table ->where($map)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('datas',$datas);// 赋值数据集
        $this->assign('page',$show);// 赋值分页输出
        if(IS_AJAX){
            $this->display('index_ajax');
        }else{
            $this->display();
        }
    }

    public function download_order()
    {
        Vendor('Crypt3Des.Crypt3Des');
        $Crypt3Des=\Crypt3Des::instance();
        $order_satas=array(
            0=>'已卖出',
            1=>'已使用',
            2=>'已退货'
        );

        $coupon_sn=I('coupon_sn');
        $inner_order_sn=I('inner_order_sn');
        $out_order_sn=I('out_order_sn');
        $status=I('status');
        $datetimeStart=I('datetimeStart');
        $datetimeEnd=I('datetimeEnd');
        $map=1;
        if($coupon_sn)
        {
            $map.=" and o.coupon_sn ='" . base64_encode($coupon_sn). "'";
        }
        if($inner_order_sn){
            $map.= " and o.inner_order_sn like '%" . $inner_order_sn . "%'";
        }
        if($out_order_sn){
            $map.= " and o.out_order_sn '%" . $out_order_sn . "%'";
        }
        if(isset($status) && $status!=''){
            $map.=" and o.status =" . $status . "";
        }
        if($datetimeStart){
            $map.= " and o.create_time>='" . $datetimeStart. "'";
        }
        if($datetimeEnd){
            $map.= " and o.create_time<='" . $datetimeEnd." 23:59:59". "'";
        }
        $table= M("boc_order o");
        $data=$table ->where($map)->order('id desc')->select();
        Vendor('Excel.PHPExcel');
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1','卡券号')
            ->setCellValue('B1','内部单号')
            ->setCellValue('C1','中行单号')
            ->setCellValue('D1','状态');

        $i=2;                //定义一个i变量，目的是在循环输出数据是控制行数
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_TEXT);
        $objPHPExcel->getActiveSheet()->getStyle('L1')->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_TEXT);
        foreach ($data as $key=>$v){
            $objPHPExcel->getActiveSheet()->setCellValueExplicit('A' . $i, $v['U_OrderId'], \PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet()->setCellValueExplicit('L' . $i, $v['U_phone'], \PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet()->getStyle('A' . $i)->getNumberFormat() ->setFormatCode("@");
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue("A".$i, $v['coupon_sn']." ")
                ->setCellValue("B".$i, $v['inner_order_sn'])
                ->setCellValue("C".$i, $v['out_order_sn'])
                ->setCellValue("D".$i,  $order_satas[$v['status']]);
            $i++;
        }
        $objPHPExcel->getSheet(0)->getColumnDimension('A')->setWidth(35);
        $objPHPExcel->getSheet(0)->getColumnDimension('E')->setWidth(22);
        $objPHPExcel->getSheet(0)->getColumnDimension('F')->setWidth(70);
        $objPHPExcel->getSheet(0)->getColumnDimension('J')->setWidth(70);
        $objPHPExcel->getSheet(0)->getColumnDimension('D')->setWidth(20);
        $objPHPExcel->getSheet(0)->getColumnDimension('L')->setAutoSize(true);

        $objPHPExcel->getActiveSheet()->setTitle('aa');
        $objPHPExcel->setActiveSheetIndex(0);
        $filename='bocOrder'. date('YmdHis',time());
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');


        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
        header('Cache-Control: max-age=0');
        $objWriter->save('php://output');
    }
    public function export()
    {
        Vendor('Util.TService');
        Vendor('Util.Crypt3Des');
        //if ($opmode == "buy") {
        $platform=trim(I('platform'));
        $numList = trim(I('numList'));
        $goodList = trim(I('goodList'));
        $goodList = mb_convert_encoding($goodList, 'UTF-8', mb_detect_encoding($goodList, array('ASCII', 'GB2312', 'GBK', 'BIG5', 'UTF-8')));
        $num_array = explode("||", $numList);
        $goodarray = explode("||", $goodList);
        $isupload=M('batch_log')->where("batch_no='".date("Y-m-d")."'")->find();
        if($isupload['status']==1)
        {
            echo  json_encode(array("status"=>201,'count'=>0,'msg'=>"导入失败，批次已上传")); exit;
        }
        $dataList = array();
        $new_goods=array();
        $couponList=array();
        //$orderList=array();
        for ($i = 0; $i < count($goodarray); $i++) {
            $arr = json_decode(rtrim($goodarray[$i], "/"), true);
            $arr['buynum'] = $num_array[$i];
            $dataList[] = $arr;
        }
        $model = M("boc_order");
        $cry = \Crypt3Des::instance('admin123', 'sdfgrwqe');
        $bocconfig = C('BOC_COUPON_CONFIG');
        $bocconfig['upload'] = DOC_ROOT . $bocconfig['upload'] . "/" . date("Y-m-d") . "/";
        if (!file_exists($bocconfig['upload'])) {
            mkdir($bocconfig['upload'], 0777, true);
        }
        foreach ($dataList as $key => $val) {
        $inteface = 'coupon/buy';

        $data = array('goodsSQ' => $val['sequence'], 'appId' => $platform, 'amount' => $val['buynum'], "outOrderId" => 'BOC' . date('YmdHis') . rand('1000', 9999));
        //$data['pageSize']=$val['buynum']>500 ? 500: $val['buynum'];
        $sign = $this->createSign($platform, $data);
        $data['sign'] = $sign;

        $res = \TService::instance()->requestService($this->baseApi . $inteface, $data);
            \Think\Log::write('买券参数：'.json_encode($res),'INFO');
            //print_r($res);exit;
        if ($res['httpCode'] == 200) {
            $coupon = $res['data']['data'];
            M('goods')->where("`id` ='".$val['id'] ."'")->setInc('stock',$val['buynum']);
        } else {
            //$this->error('接口请求失败！', U('/Admin/bocvirtualcard/export'));
            //exit;
        }
        if (!isset($coupon['orderId']) || $coupon['orderId'] == '') {
            //$this->error('库存不足', U('/Admin/bocvirtualcard/export'));
            //exit;
        }

        $inteface = 'coupon/querycoupon';
        $data = array('appId' => $platform, 'orderNo' => $coupon['orderId'], 'page' => 1,'pageSize'=>500);
        $sign = $this->createSign($platform, $data);
        $data['sign'] = $sign;
        $res = \TService::instance()->requestService($this->baseApi . $inteface, $data);
        if ($res['httpCode'] == 200) {
            $couponArray = $res['data']['couponList'];
            if(count($couponArray)>0)
            {
              $couponList=array_merge($couponList,$couponArray) ;
            }
        }


        foreach ($couponArray as $k => $v) {
            $goodsInfo=M('goods g')->where("g.id = '" .$val['id']. "'")->find();
            $goods_uplod_exis = M('boc_ids')->where("goods_id='" . $val['id'] . "' and expire='" . $goodsInfo['expire'] . "'")->order('id desc')->limit(1)->find();
            if ($goods_uplod_exis) {
                $goods_code = $goods_uplod_exis['boc_goods_id_code'];
            } else {
                $goods_ids = M('boc_ids')->where("goods_id='" . $val['id'] . "'")->order('id desc')->limit(1)->find();
                if ($goods_ids) {
                    $goods_code = $goods_ids['boc_goods_id_code'] + 1;
                } else {
                    $goods_code = sprintf('%-04s', $val['id']) . sprintf('%02s', 1);

                }
                M('boc_ids')->add(
                    array(
                        'boc_goods_id_code' => $goods_code,
                        'goods_id' => $val['id'],
                        'expire' => $goodsInfo['expire'],
                        //'num' => 3,
                        'add_time' => date("Y-m-d H:i:s")
                    )
                );

                //$new_goods[]=array('goods_id' => $v['goods_id'], 'goods_code' => $goods_code, 'expire' => $v['expire']);

            }
            $insertData = array(
                'coupon_sn' => $v['code'],
                'create_time' => date("Y-m-d H:i:s"),
                'inner_order_sn' => $coupon['orderId'],
                'handle_name' => session('user_auth.username'),
                'goods_id'=>$val['id'],
                'boc_goods_id' => $goods_code,
                 'coupon_type'=>$val['type'],
                'sequence' => $val['sequence'],
                'batch_no'=>"BFSH_".date("Y-m-d"),
                'expire'=>strtotime($v['expire']),
                'stock_code'=>mb_substr($v['goods_id'], 0, 4) . rand(100000, 999999),
                'price'=>$goodsInfo['price'],
                'integral'=>$goodsInfo['integral'],
                'platform'=>$platform,
                'settle_price'=>$goodsInfo['settle_price']

            );

            //$orderList[] =  $insertData;
           // $outputline .= sprintf('%-11s', 'WSHHY' . $goods_code) . " |#| " . mb_substr($v['goods_id'], 0, 4) . rand(100000, 999999) . " |#| " . "E" . " |#| " . sprintf('%-36s', base64_decode($v['code'])) . " |#| " . " |#| " . " |#| " . " |#| " . "\n";
            $model->add($insertData);
        }
    }
    //var_dump($couponList);exit;
    $count=empty($couponList) ? 0 :count($couponList);
    echo  json_encode(array("status"=>200,'count'=>$count)); exit;

    }


    public function bfshProductexport()
    {
        Vendor('Util.TService');
        Vendor('Util.Crypt3Des');
        //if ($opmode == "buy") {
        //$platform=trim(I('platform'));
        $platform="C200866";
        $numList = trim(I('numList'));
        $goodList = trim(I('goodList'));
        $goodList = mb_convert_encoding($goodList, 'UTF-8', mb_detect_encoding($goodList, array('ASCII', 'GB2312', 'GBK', 'BIG5', 'UTF-8')));
        $num_array = explode("||", $numList);
        $goodarray = explode("||", $goodList);
        $isupload=M('batch_log')->where("batch_no='".date("Y-m-d")."'")->find();
        if($isupload['status']==1)
        {
            echo  json_encode(array("status"=>201,'count'=>0,'msg'=>"导入失败，批次已上传")); exit;
        }
        $dataList = array();
        $new_goods=array();
        $couponList=array();
        //$orderList=array();
        for ($i = 0; $i < count($goodarray); $i++) {
            $arr = json_decode(rtrim($goodarray[$i], "/"), true);
            $arr['buynum'] = $num_array[$i];
            $dataList[] = $arr;
        }
        $model = M("boc_order");
        $cry = \Crypt3Des::instance('admin123', 'sdfgrwqe');
        $bocconfig = C('BOC_COUPON_CONFIG');
        $bocconfig['upload'] = DOC_ROOT . $bocconfig['upload'] . "/" . date("Y-m-d") . "/";
        if (!file_exists($bocconfig['upload'])) {
            mkdir($bocconfig['upload'], 0777, true);
        }
        foreach ($dataList as $key => $val) {
            $inteface = 'coupon/buylocal';

            $data = array('goodsSQ' => $val['sequence'], 'appId' => $platform, 'amount' => $val['buynum'], "outOrderId" => 'BOC' . date('YmdHis') . rand('1000', 9999));
            //$data['pageSize']=$val['buynum']>500 ? 500: $val['buynum'];
            $sign = $this->createSign($platform, $data);
            $data['sign'] = $sign;

            $res = \TService::instance()->requestService($this->productbaseApi . $inteface, $data);
            \Think\Log::write('买券参数：'.json_encode($res),'INFO');
            //print_r($res);exit;
            if ($res['httpCode'] == 200) {
                $coupon = $res['data']['data'];
                M('goods')->where("`id` ='".$val['id'] ."'")->setInc('stock',$val['buynum']);
            } else {
                //$this->error('接口请求失败！', U('/Admin/bocvirtualcard/export'));
                //exit;
            }
            if (!isset($coupon['orderId']) || $coupon['orderId'] == '') {
                //$this->error('库存不足', U('/Admin/bocvirtualcard/export'));
                //exit;
            }

            $inteface = 'coupon/querycoupon';
            $data = array('appId' => $platform, 'orderNo' => $coupon['orderId'], 'page' => 1,'pageSize'=>500);
            $sign = $this->createSign($platform, $data);
            $data['sign'] = $sign;
            $res = \TService::instance()->requestService($this->productbaseApi . $inteface, $data);
            if ($res['httpCode'] == 200) {
                $couponArray = $res['data']['couponList'];
                if(count($couponArray)>0)
                {
                    $couponList=array_merge($couponList,$couponArray) ;
                }
            }


            foreach ($couponArray as $k => $v) {
                $goodsInfo=M('goods g')->where("g.id = '" .$val['id']. "'")->find();
                $goods_uplod_exis = M('boc_ids')->where("goods_id='" . $val['id'] . "' and expire='" . $goodsInfo['expire'] . "'")->order('id desc')->limit(1)->find();
                if ($goods_uplod_exis) {
                    $goods_code = $goods_uplod_exis['boc_goods_id_code'];
                } else {
                    $goods_ids = M('boc_ids')->where("goods_id='" . $val['id'] . "'")->order('id desc')->limit(1)->find();
                    if ($goods_ids) {
                        $goods_code = $goods_ids['boc_goods_id_code'] + 1;
                    } else {
                        $goods_code = sprintf('%-04s', $val['id']) . sprintf('%02s', 1);

                    }
                    M('boc_ids')->add(
                        array(
                            'boc_goods_id_code' => $goods_code,
                            'goods_id' => $val['id'],
                            'expire' => $goodsInfo['expire'],
                            //'num' => 3,
                            'add_time' => date("Y-m-d H:i:s")
                        )
                    );

                    //$new_goods[]=array('goods_id' => $v['goods_id'], 'goods_code' => $goods_code, 'expire' => $v['expire']);

                }
                $insertData = array(
                    'coupon_sn' => $v['code'],
                    'create_time' => date("Y-m-d H:i:s"),
                    'inner_order_sn' => $coupon['orderId'],
                    'handle_name' => session('user_auth.username'),
                    'goods_id'=>$val['id'],
                    'boc_goods_id' => $goods_code,
                    'coupon_type'=>$val['type'],
                    'sequence' => $val['sequence'],
                    'batch_no'=>"BFSW_".date("Y-m-d"),
                    'expire'=>strtotime($v['expire']),
                    'stock_code'=>mb_substr($v['goods_id'], 0, 4) . rand(100000, 999999),
                    'price'=>$goodsInfo['price'],
                    'integral'=>$goodsInfo['integral'],
                    'platform'=>$platform,
                    'settle_price'=>$goodsInfo['settle_price']

                );

                //$orderList[] =  $insertData;
                // $outputline .= sprintf('%-11s', 'WSHHY' . $goods_code) . " |#| " . mb_substr($v['goods_id'], 0, 4) . rand(100000, 999999) . " |#| " . "E" . " |#| " . sprintf('%-36s', base64_decode($v['code'])) . " |#| " . " |#| " . " |#| " . " |#| " . "\n";
                $model->add($insertData);
            }
        }
        //var_dump($couponList);exit;
        $count=empty($couponList) ? 0 :count($couponList);
        echo  json_encode(array("status"=>200,'count'=>$count)); exit;
    }


    public function bcspexport()
    {
        Vendor('Util.TService');
        Vendor('Util.Crypt3Des');
        $numList = trim(I('numList'));
        $goodList = trim(I('goodList'));
        $goodList = mb_convert_encoding($goodList, 'UTF-8', mb_detect_encoding($goodList, array('ASCII', 'GB2312', 'GBK', 'BIG5', 'UTF-8')));
        $num_array = explode("||", $numList);
        $goodarray = explode("||", $goodList);
        $isupload=M('batch_log')->where("batch_no='".'BCSP_'.date("Y-m-d")."'")->find();
        if($isupload['status']==1)
        {
            echo  json_encode(array("status"=>201,'count'=>0,'msg'=>"导入失败，批次已上传")); exit;
        }
        $dataList = array();
        $new_goods=array();
        $couponList=array();
        //$orderList=array();
        for ($i = 0; $i < count($goodarray); $i++) {
            $arr = json_decode(rtrim($goodarray[$i], "/"), true);
            $arr['buynum'] = $num_array[$i];
            $dataList[] = $arr;
        }
        $model = M("boc_order");
        $cry = \Crypt3Des::instance('admin123', 'sdfgrwqe');
        foreach ($dataList as $key => $val) {
            $inteface = 'coupon/buy';

            $data = array('goodsSQ' => $val['sequence'], 'appId' => 'C20123', 'amount' => $val['buynum'], "outOrderId" => 'BOC' . date('YmdHis') . rand('1000', 9999));
            //$data['pageSize']=$val['buynum']>500 ? 500: $val['buynum'];
            $sign = $this->createSign('C20123', $data);
            $data['sign'] = $sign;
            $res = \TService::instance()->requestService($this->baseApi . $inteface, $data);
            \Think\Log::write('买券参数：'.json_encode($res),'INFO');
            //print_r($res);exit;
            if ($res['httpCode'] == 200) {
                $coupon = $res['data']['data'];
                M('goods')->where("`id` ='".$val['id'] ."'")->setInc('stock',$val['buynum']);
            } else {
                //$this->error('接口请求失败！', U('/Admin/bocvirtualcard/export'));
                //exit;
            }
            if (!isset($coupon['orderId']) || $coupon['orderId'] == '') {
                //$this->error('库存不足', U('/Admin/bocvirtualcard/export'));
                //exit;
            }

            $inteface = 'coupon/querycoupon';
            $data = array('appId' => 'C20123', 'orderNo' => $coupon['orderId'], 'page' => 1,'pageSize'=>500);
            $sign = $this->createSign('C20123', $data);
            $data['sign'] = $sign;
            $res = \TService::instance()->requestService($this->baseApi . $inteface, $data);
            if ($res['httpCode'] == 200) {
                $couponArray = $res['data']['couponList'];
                if(count($couponArray)>0){
                  $couponList=array_merge($couponList,$couponArray) ;
                }
            }
            foreach ($couponArray as $k => $v) {
                $goodsInfo=M('goods g')->where("g.id = '" .$val['id']. "'")->find();
                $insertData = array(
                    'coupon_sn' => $v['code'],
                    'create_time' => date("Y-m-d H:i:s"),
                    'inner_order_sn' => $coupon['orderId'],
                    'handle_name' => session('user_auth.username'),
                    'goods_id'=>$val['id'],
                    'coupon_type'=>$val['type'],
                    'sequence' => $val['sequence'],
                    'batch_no'=>"BCSP_".date("Y-m-d"),
                    'expire'=>strtotime($v['expire']),
                    'price'=>$goodsInfo['price'],
                    'integral'=>$goodsInfo['integral'],
                    'settle_price'=>$goodsInfo['settle_price'],
                    'platform'=>'C20123'
                );

                //$orderList[] =  $insertData;
                // $outputline .= sprintf('%-11s', 'WSHHY' . $goods_code) . " |#| " . mb_substr($v['goods_id'], 0, 4) . rand(100000, 999999) . " |#| " . "E" . " |#| " . sprintf('%-36s', base64_decode($v['code'])) . " |#| " . " |#| " . " |#| " . " |#| " . "\n";
                $model->add($insertData);
            }
        }

        $count=empty($couponList) ? 0 :count($couponList);
        echo  json_encode(array("status"=>200,'count'=>$count)); exit;
    }

    private  function createSign($appkey,$array){
        ksort($array);
        $str = '';
        foreach ($array as $key=>$va){
            $str.=$key.$va;
        }
        $str.=$appkey;
        return md5($str);
    }



    private function unzip($path,$zipName){
        $resource = zip_open($path.$zipName);
        while ($dir_resource = zip_read($resource)) {
            $file_content = zip_entry_read($dir_resource,1000000);
            $name =  zip_entry_name($dir_resource);
            $res+= file_put_contents($path.$name,$file_content);

        }
        zip_close($resource);
        return $res;

    }
    /**
     *
     * @param string $filename  压缩包位置名称
     * @param unknown $files   需要添加到压缩包文件
     */
    function zip($filename ='',$files){
        $zip = new ZipArchive();
        if ($zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE) {
            return false;
        }
        if(file_exists($files)){
            $zip->addFile( $files, basename($files));

        }

        $res = $zip->numFiles;
        $zip->close();

        return $res;
    }

    function addzip($zipname,$fileToZip,$newFileName){
        $zip = new ZipArchive;
        if ($zip -> open($zipname,ZIPARCHIVE::CREATE) === TRUE) {
            if(file_exists($fileToZip))
            {
                $zip->addFile($fileToZip,$newFileName);
            }
            if($zip -> close()){
                $res = $zip->numFiles;
                return $res;
            }else{
                return false;
            }
        } else {
            return false;
        }
    }


    public function ajax_get_goods_name()
    {
        if(I('keyword')){
            $keyword = I('keyword');
            $map['name'] = array('like','%'.$keyword.'%');
        }
        $goods_name = M('goods')->where($map)->order('id desc')->field('id,name')->select();
        foreach($goods_name as $k=>$v)
        {
            $goods_name[$k]['num']=M('coupons')  ->where('goods_id='.$v['id']." and cstatus=1 and expire>".time())->count();
        }
        $this->ajaxReturn($goods_name);
    }
    private function filling_str($str,$length)
    {
       $strlength=(strlen($str)+mb_strlen($str,"UTF8"))/2;
       if($strlength<$length)
       {
           for($i=0;$i<($length-$strlength);$i++)
           {
               $str.=" ";
           }
       }
       return $str;
    }



    private function httpcopy($url, $filename,$dir, $timeout=60) {
        $ext = strrchr($url, '.');
        if($ext != '.gif' && $ext != ".jpg" && $ext != ".bmp" && $ext != ".jpeg" && $ext != ".png"){
            echo "格式不支持！";
            return false;
        }
        $filename = $dir .$filename.$ext;
        if(!function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            $temp = curl_exec($ch);
            $fp2=@fopen($filename,'a');
            fwrite($fp2,$temp);
            fclose($fp2);
            if(@file_put_contents( $filename, $temp) && !curl_error($ch)) {
                return  $filename ;
            } else {
                return false;
            }
        } else {
            $opts = array(
                "http"=>array(
                    "method"=>"GET",
                    "header"=>"",
                    "timeout"=>$timeout)
            );
            $context = stream_context_create($opts);
            if(@copy($url,  $filename , $context)) {
                return  $filename ;
            } else {
                return false;
            }
        }
    }

}