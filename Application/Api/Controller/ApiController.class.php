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
use ZipArchive;

/*
 *
 * 中行卡券实时接口
 *
 */
class ApiController extends Controller {
    /*
     * 退货信息接口
     */
    public function index(){
       echo 9999;exit;
    }
    /*
   * 卡券已使用更新接口
   */
    public function syncToCouponApi()
    {
        //\Think\Log::write('请求Url：'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'],'INFO');
        $result=array("status"=>200,'msg'=>'OK');
        $coupon_sn=I('coupon');
        $phone=trim(I('phone'));
        $status=I('status');
        $sign=I("sign");
        //$price=I("price");
        $data=array('coupon_sn'=>$coupon_sn,'phone'=>$phone);

        $orderInfo=M("boc_order")->where("coupon_sn='".$this->urlsafe_b64encode(trim($coupon_sn))."' and  platform in('C20123','C20130','C20122','C200866')")->find();

        if(empty($orderInfo))
        {

            $result['status']=203;
            $result['msg']="订单不存在";
            echo json_encode($result);exit;
        }
//        if(!$this->checkSign($sign,$data))
//        {
//            $result['status']=201;
//            $result['msg']="签名错误";
//            echo json_encode($result);exit;
//        }
        $data['use_time']=date("Y-m-d");
        $data['use_status']=$status;
        //try{
            unset($data['coupon_sn']);
            M("boc_order")->where("coupon_sn='".$this->urlsafe_b64encode(trim($coupon_sn))."'")->save($data);
            if($status==1 &&  ($orderInfo['platform']=="C20122" || $orderInfo['platform']=="C20130"  || $orderInfo['platform']=='C200866'))
            {
                $json_array=array(
                    "waresId"=> "WSHHY".$orderInfo['boc_goods_id'],
                    //"wEid"=>$orderInfo['stock_code'],
                    "wEid"=>"1" . sprintf('%09s', $orderInfo['id']),
                    //sprintf('%011s', $v['id'])
                    "wSign"=>"E",
                    "wInfo"=>$coupon_sn,
                    "usedDate"=>date("Ymd"),
                    "UsedTime"=>date("H:i:s")
                    //https://101.231.206.213/Coupons/couponUsed.do
                );

                $json_data='"'.str_replace('"','\"',json_encode($json_array)).'"';
                \Think\Log::write('json_data：'.$json_data,'INFO');
                //echo  $json_data."<br>";
                if (PATH_SEPARATOR==':') {
                    $task="cd ". DOC_ROOT . "/Uploads/Boccoupon/Library/RSA/" . ";java -jar RsaEncryUtil.jar ".   DOC_ROOT . "/Uploads/Boccoupon/Library/RSA/cert/bocpub.der ". $json_data;
                } else {
                    $task = substr(DOC_ROOT, 0, 2) . "&cd " . DOC_ROOT . "/Uploads/Boccoupon/Library/RSA/" . "&java -jar RsaEncryUtil.jar "  . DOC_ROOT . "/Uploads/Boccoupon/Library/RSA/cert/bocpub.der ". $json_data;
                }
                exec( $task." 2>&1",$output);//


                if($this->is_json($output[0]))
                {
                    \Think\Log::write('加密参数：'.$output[0],'INFO');
                    $outresult=json_decode($output[0],true);
                    if($outresult['status']==200)
                    {
                        $res= $outresult['response'];
                        $url='https://mlife.jf365.boc.cn/CouponsMall/couponUsed.do?requestType=S&data='.$res;
                        \Think\Log::write('请求url：'.$url,'INFO');
                        $res=$this->getHttpContent($url, $method = 'GET', array());
                        \Think\Log::write('获取银行结果：'.json_encode($res),'INFO');
                        if($res["stat"]=="00")
                        {
                            M("boc_order")->where("coupon_sn='".$this->urlsafe_b64encode(trim($coupon_sn))."'")->save(array('syns_status'=>1,'syns_time'=>date("Y-m-d")));
                            M('goods')->where("`sequence` ='".$orderInfo['goods_id']."'")->setDec('stock',1);

                        }else{
                            $result['status']=202;
                            $result['msg']="同步银行失败";
                        }
                    }
                }
            }

//        }catch(exception $e)
//        {
//            $result['status']=202;
//            $result['msg']="同步失败";
//        }
        echo json_encode($result);exit;

    }


    public function syncToBoc()
    {
        $arr=M('boc_order')->where(" use_status=1 and syns_status=0 and platform in('C20130','C20122') ")->limit(100)->select();

        //$coupons="";
        foreach($arr as $k=>$v) {

                $json_array = array(
                    "waresId" => "WSHHY" . $v['boc_goods_id'],
                    //"wEid"=>$orderInfo['stock_code'],
                    "wEid" => "1" . sprintf('%09s', $v['id']),
                    //sprintf('%011s', $v['id'])
                    "wSign" => "E",
                    "wInfo" => base64_decode($v['coupon_sn']),
                    "usedDate" => date("Ymd"),
                    "UsedTime" => date("H:i:s")
                    //https://101.231.206.213/Coupons/couponUsed.do
                );
                $json_data = '"' . str_replace('"', '\"', json_encode($json_array)) . '"';
                \Think\Log::write('json_data：' . $json_data, 'INFO');
                if (PATH_SEPARATOR == ':') {
                    $task = "cd " . DOC_ROOT . "/Uploads/Boccoupon/Library/RSA/" . ";java -jar RsaEncryUtil.jar " . DOC_ROOT . "/Uploads/Boccoupon/Library/RSA/cert/bocpub.der " . $json_data;
                } else {
                    $task = substr(DOC_ROOT, 0, 2) . "&cd " . DOC_ROOT . "/Uploads/Boccoupon/Library/RSA/" . "&java -jar RsaEncryUtil.jar " . DOC_ROOT . "/Uploads/Boccoupon/Library/RSA/cert/bocpub.der " . $json_data;
                }
                exec($task . " 2>&1", $output);//


                if ($this->is_json($output[0])) {
                    \Think\Log::write('加密参数：' . $output[0], 'INFO');
                    $outresult = json_decode($output[0], true);
                    if ($outresult['status'] == 200) {
                        $res = $outresult['response'];
                        $url = 'https://mlife.jf365.boc.cn/CouponsMall/couponUsed.do?requestType=S&data=' . $res;
                        \Think\Log::write('请求url：' . $url, 'INFO');
                        $res = $this->getHttpContent($url, $method = 'GET', array());
                        \Think\Log::write('获取银行结果：' . json_encode($res), 'INFO');
                        if ($res["stat"] == "00") {
                            M("boc_order")->where("coupon_sn='" . $v['coupon_sn'] . "'")->save(array('syns_status' => 1, 'syns_time' => date("Y-m-d")));
                            //M('goods')->where("`sequence` ='" . $orderInfo['goods_id'] . "'")->setDec('stock', 1);

                        } else {
                            $result['status'] = 202;
                            $result['msg'] = "同步银行失败";
                        }
                    }
                }


        }

    }

    public function syncTocSap()
    {
            $arr=M('boc_order')->where(" use_status=1 and syns_status=0 and platform in('C20123','C20130','C20122') ")->limit(10)->select();
            $coupons="";
            foreach($arr as $k=>$v)
            {
                $coupons.="'".$v['coupon_sn']."',";
                $details[]=array(
                    "orderId"=>"B_".sprintf('%016s', $v['id']),
                    "channelCode"=>$v['platform'],
                    "orderType"=>2,
                    "price"=>intval($v['settle_price']*100),
                    "num"=>1,
                    "totalPrice"=>intval($v['settle_price']*100),
                    'skuCode'=>$v['sequence'],
                    'warehouseCode'=> "A02",
                    "orderNo"=>$v['coupon_sn'],
                    "taxRate"=>0

                );
                $post_data[]=array(

                    'id'=> "B_".sprintf('%016s', $v['id']),
                    "channelCode"=>$v['platform'],
                    "orderNo"=>$v['coupon_sn'],
                    "discount"=>0,
                    "productTotal"=>intval($v['settle_price']*100),
                    "orderTotal"=>intval($v['settle_price']*100),
                    'details'=>$details


                );
                unset($details);

            }

           $coupons= rtrim($coupons,",");
            //$url="http://192.168.1.9:8000/BOCVirtualVolume/order"; ////测式url
            //$url="http://192.168.1.66:8008/BOCVirtualVolume/order";
            //$url="http://58.247.49.90:8008/videoCard/order";  //正式url

            $url="http://58.247.49.90:8007/BOCVirtualVolume/order";


          //$url="http://58.247.49.90:8007/videoCard/order";
            $res=$this->getHttp($url, json_encode($post_data));

             //print_r($res);
            \Think\Log::write('加密参数：'.json_encode($res),'INFO');
            if($res["httpCode"]=="200")
            {
                M("boc_order")->where("`coupon_sn` in(".rtrim($coupons,",").")")->save(array('syns_status'=>1,'syns_time'=>date("Y-m-d")));
                echo M("boc_order")->getLastSql();
                echo "同步sap成功";
            }else{
                echo "同步sap失败";
            }
            exit;
            //

        }
        public function syncToOmsib()
       {
           $arr=M('boc_order')->where(" use_status=1 and syns_status=0 ")->limit(10)->select();
           $coupons="";
           foreach($arr as $k=>$v)
           {
               $coupons.="'".$v['coupon_sn']."',";
               $post_data[]=array(
                   'itemCode'=>$v['settle_price'],
                   'count'=> 1,
                   'price'=>$v['settle_price'],
                   'userNanme'=>'BOC',
                   'phone'=>$v['phone'],
                   'address'=>'上海市杨浦区政立路415号A座9楼',
                   'orderId'=>"B_".sprintf('%015s', $v['id']),
                   'platform'=>$v['platform'],
                   'isFinished'=>1

               );
               unset($details);
           }

           $coupons= rtrim($coupons,",");

           $url="http://192.168.1.250/orders/push";


           //$url="http://58.247.49.90:8007/videoCard/order";
           $res=$this->getHttp($url, json_encode($post_data));

           //print_r($res);
           \Think\Log::write('加密参数：'.json_encode($res),'INFO');
           if($res["httpCode"]=="0")
           {
               M("boc_order")->where("`coupon_sn` in(".rtrim($coupons,",").")")->save(array('syns_status'=>1,'syns_time'=>date("Y-m-d")));
               echo M("boc_order")->getLastSql();
               echo "同步sap成功";
           }else{
               echo "同步sap失败";
           }
           exit;

       }

        public function expireCoupon()
        {
            $time=strtotime(date("Y-m-d"),time());
            $table=M('boc_order');
            $sql="SELECT g.`name`,g.`sequence`,g.`price`,CASE g.`platform` WHEN 'C20122' THEN '缤纷生活' ELSE '尊享积分' END AS chanle,g.`expire`,o.coupon_sn,o.batch_no FROM vrc_boc_order o JOIN  `vrc_goods` g ON o.goods_id=g.id  WHERE  g.`platform`='C20122' AND  g.expire>".$time;
            $list=$table->query($sql);
            Vendor('Excel.PHPExcel');
            $resultPHPExcel = new \PHPExcel();
            $resultPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(50);
            $resultPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
            $resultPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
            $resultPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
            $resultPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
            $resultPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
            $resultPHPExcel->getActiveSheet()->setCellValue('A1',"商品编码");
            $resultPHPExcel->getActiveSheet()->setCellValue('B1',"商品名称");
            $resultPHPExcel->getActiveSheet()->setCellValue('C1',"商品价格");
            $resultPHPExcel->getActiveSheet()->setCellValue('D1',"批次号");
            $resultPHPExcel->getActiveSheet()->setCellValue('E1',"券码");
            $resultPHPExcel->getActiveSheet()->setCellValue('F1',"到期时间");


            $i=2;
            foreach($list as $key=>$v){
                $resultPHPExcel->getActiveSheet()->setCellValue('A'.$i, $v['sequence']);
                $resultPHPExcel->getActiveSheet()->setCellValue('B'.$i, $v['name']);
                $resultPHPExcel->getActiveSheet()->setCellValue('C'.$i, $v['price']);
                $resultPHPExcel->getActiveSheet()->setCellValue('D'.$i, $v['batch_no']);
                $resultPHPExcel->getActiveSheet()->setCellValue('E'.$i, base64_decode($v['coupon_sn']));
                $resultPHPExcel->getActiveSheet()->setCellValue('F'.$i, date("Y-m-d H:i:s",$v['expire']));
                $i++;
            }

            $savefile = "卡券导出报表".date('YmdHis',time());
            $resultPHPExcel->getActiveSheet()->setTitle('卡券导出'); //题目
            $resultPHPExcel->setActiveSheetIndex(0); //设置当前的sheet
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="' . $savefile . '.xls"'); //文件名称
            header('Cache-Control: max-age=0');
            $objWriter = \PHPExcel_IOFactory::createWriter($resultPHPExcel, 'Excel2007'); //Excel5 Excel2007
            $objWriter->save('php://output');

        }
    public function bcspexport()
    {
        set_time_limit(0);
        @ini_set("max_execution_time", 0);
        $model = M("boc_order");
        $bocconfig = C('BOC_COUPON_CONFIG');
        $outputline='TxnDetailStart'. "\n";
        //$batch_no=trim(I('batch_no'));
        $batch_no="BCSP_2018-12-04";
        $bocconfig['upload'] = DOC_ROOT . $bocconfig['upload'] . "" . $batch_no . "/";
        $isupload=M('batch_log')->where("batch_no='".$batch_no."'")->find();

        if($isupload['status']==1)
        {
            $this->error('批次已经上传了',U('/Admin/Purchase/index'));
        }
        if (!file_exists($bocconfig['upload'])) {
            mkdir($bocconfig['upload'], 0777, true);
        }
        $dataList=$model->where("batch_no='".$batch_no."' and platform='C20123' ")->field('id,coupon_sn,goods_id')->select();
        $data=array_chunk($dataList,100);
        foreach($data as $couponList)
        {
            $json_data = '"' . str_replace('"', '\"', json_encode($couponList)) . '"';
            if (PATH_SEPARATOR == ':') {
                $task = "cd " . DOC_ROOT . "/Uploads/Boccoupon/Library/" . ";java -jar Bcspencrypt_fat.jar " . $json_data;
            } else {
                $task = substr(DOC_ROOT, 0, 2) . "&cd " . DOC_ROOT . "/Uploads/Boccoupon/Library/" . "&java -jar  Bcspencrypt_fat.jar  " . $json_data;
            }
            exec($task . " 2>&1", $out);
            if ($this->is_json($out[0])) {
                $encryptData = json_decode(trim($out[0]), true);
                foreach ($couponList as $k => $v) {
                    //$goodsInfo = M('goods g')->join('vrc_bcsp_goods_code c on g.bcsp_goods_code=c.id')->where("g.id = '" . $v['goods_id'] . "'")->field("c.bcsp_goods_code")->find();
                    //$goodsInfo = M('goods g')->join('vrc_bcsp_goods_code c on g.bcsp_goods_code=c.id')->where("g.id = '" . $v['goods_id'] . "'")->field("c.bcsp_goods_code")->find();
                    $bcsp_goods_code=M('goods')->where("id = '".trim($v['goods_id'])."'")->getField('bcsp_goods_code');
                    $outputline .= sprintf('%015s', $v['id']) . "||" . $bcsp_goods_code . "||SHHY_" . sprintf('%015s', $v['id']) . "||" . $encryptData[base64_decode($v['coupon_sn'])] . "||" . "||" . "||" . "||" . "||" . "\n";
                }
            }
            unset($out);
        }
        $outputline .= 'TotalRecord:' . sprintf('%010s', count($dataList));


        $filename = 'PARTNET03_001_'.date("Ymd",strtotime("-1 day")).'.TXT';
        $outfile="PARTNET03_001_".date("Ymd",strtotime("-1 day"));
        //$filename = 'PARTNET03_001_'.date("Ymd").'.TXT';
        //$outfile="PARTNET03_001_".date("Ymd");
        file_put_contents($bocconfig['upload'].$filename,mb_convert_encoding($outputline,'GBK',mb_detect_encoding($outputline,array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'))));
        if (PATH_SEPARATOR==':') {
            exec(" cd {$bocconfig['upload']} && zip -j {$outfile}.zip  {$filename}", $output);
        } else {
            $this->zip($bocconfig['upload'] . $outfile. '.zip', $bocconfig['upload'] . $filename);

        }
        if(file_exists($bocconfig['upload'] .  $outfile.".zip")) {
            if (PATH_SEPARATOR==':') {
                $task="cd ". DOC_ROOT . "/Uploads/Boccoupon/Library/" . ";java -jar PgpEncryUtil.jar ". $bocconfig['upload'] . $outfile . '.zip ' . $bocconfig['upload'] .   $outfile .".zip.pgp ". DOC_ROOT . "/Uploads/Boccoupon/Cert/public_partner.asc";
            } else {
                $task = substr(DOC_ROOT, 0, 2) . "&cd " . DOC_ROOT . "/Uploads/Boccoupon/Library/" . "&java -jar PgpEncryUtil.jar " . $bocconfig['upload'] .  $outfile . '.zip ' . $bocconfig['upload'] . $outfile. ".zip.pgp ".DOC_ROOT . "/Uploads/Boccoupon/Cert/public_partner.asc";
            }
        }
        exec($task . " 2>&1", $out);
        if ($out[0] == 'success') {
            exit;
            if (PATH_SEPARATOR==':') {
                $task = "cd " . DOC_ROOT . "/Uploads/Boccoupon/Library/BCSPSFTP/" . ";java -jar Sftpupload_fat.jar /out " . $bocconfig['upload'] .  $outfile . '.zip.pgp';
            } else {
                $task = substr(DOC_ROOT, 0, 2) . "&cd " . DOC_ROOT . "/Uploads/Boccoupon/Library/BCSPSFTP/" . "&java -jar  Sftpupload_fat.jar /out " . $bocconfig['upload'] .  $outfile . '.zip.pgp';
            }
            exec($task . " 2>&1", $out);
            if ($out[0] == 'success') {
                M('batch_log')->add(array('batch_no'=>$batch_no,'status'=>1,'updatetime'=>time()));
                $this->success('上传成功', U('/Admin/bocvirtualcard/index'));

            } else {
                $this->error('上传商户文件失败', U('/Admin/Purchase/bcsplist'));
            }
        }

    }


    public function export()
    {
        $batch_no="BFSH_".date("Y-m-d");
        $model = M("boc_order");
        $bocconfig = C('BOC_COUPON_CONFIG');
        $outputline='';
        $new_goods=array();
        //$batch_no=trim(I('batch_no'));
        $bocconfig['upload'] = DOC_ROOT . $bocconfig['upload'] . "/" . $batch_no . "/";
        $isupload=M('batch_log')->where("batch_no='".$batch_no."'")->find();
//        if($isupload['status']==1)
//        {
//            $this->error('批次已经上传了',U('/Admin/Purchase/index'));
//        }
        if (!file_exists($bocconfig['upload'])) {
            mkdir($bocconfig['upload'], 0777, true);
        }
        $couponList=$model->where("batch_no='".$batch_no."' and platform='C20130' ")->select();
        foreach ($couponList as $k => $v) {

            $goods_uplod_exis=M('boc_ids')->where("boc_goods_id_code='" . $v['boc_goods_id'] . "' and `status`=0")->order('id desc')->limit(1)->find();
            if ($goods_uplod_exis) {
                $new_goods[]=array('goods_id' => $v['goods_id'], 'goods_code' => $v['boc_goods_id'], 'expire' => date("Y-m-d",$v['expire']));
                M('boc_ids')->where("goods_id='".$v['goods_id']."'and `boc_goods_id_code`='".$v['boc_goods_id']."'")->save(array('status'=>1));
                //$goods_code = $goods_uplod_exis['boc_goods_id_code'];
            }
            $outputline .= sprintf('%-11s', 'WSHHY' . $v['boc_goods_id']) . " |#| 1" . sprintf('%09s', $v['id']). " |#| " . "E" . " |#| " . sprintf('%-36s', base64_decode($v['coupon_sn'])) . " |#| " . " |#| " . " |#| " . " |#| " . "\n";
        }

        $outputline.='TLRL'. sprintf('%015s', count($couponList));
        $filename = 'COUPON' . '.SHHY.'. date('Ymd').'.00'.'.P';
        file_put_contents($bocconfig['upload'].$filename,mb_convert_encoding($outputline,'GBK',mb_detect_encoding($outputline,array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'))));
        if (PATH_SEPARATOR==':') {
            exec(" cd {$bocconfig['upload']} && zip -j {$filename}.ZIP  {$filename}", $output);
        } else {
            $this->zip($bocconfig['upload'] . $filename . '.ZIP', $bocconfig['upload'] . $filename);
        }
        if(file_exists($bocconfig['upload'] . $filename . '.ZIP')) {
            if (PATH_SEPARATOR==':') {
                $task="cd ". DOC_ROOT . "/Uploads/Boccoupon/Library/" . ";java -jar PgpEncryUtil.jar ". $bocconfig['upload'] . $filename . '.ZIP ' . $bocconfig['upload'] . $filename . '.ZIP.DAT ' . DOC_ROOT . "/Uploads/Boccoupon/Cert/dsfpublic.asc";
            } else {
                $task = substr(DOC_ROOT, 0, 2) . "&cd " . DOC_ROOT . "/Uploads/Boccoupon/Library/" . "&java -jar PgpEncryUtil.jar " . $bocconfig['upload'] . $filename . '.ZIP ' . $bocconfig['upload'] . $filename . '.ZIP.DAT ' . DOC_ROOT . "/Uploads/Boccoupon/Cert/dsfpublic.asc";

            }
        }

        exec( $task." 2>&1",$out);//

        if($out[0]!='success')
        {
            $this->error('加密卡券文件失败',U('/Admin/Purchase/index'));
        }
        $goodsFileName="WARES.SHHY.".date("Ymd").".00.P";
        $picFileName="PIC.SHHY.".date("Ymd").".00.P";
        $goodsoutputline='';

        foreach($new_goods  as $k=>$v)
        {
            $goodsInfo=M('goods g')->where("g.id = '" .$v['goods_id']. "'")->field("g.id as goods_id,g.name,g.price,g.integral,g.pic_url,g.expire,g.type,g.description1,g.description2,g.description3,g.description4,g.description5,g.description6,g.description7,g.description8,g.description9,g.description10")->find();
            //$goodsoutputline.= sprintf('%-11s','WSHHY'. $v['goods_code'])." |#| ".$this->filling_str($goodsInfo['name'],40)." |#| "."03"." |#| "."0210"." |#| ".sprintf('%-10s', 'MSHHY00001') ." |#| ".sprintf('%-11s', '') ." |#| ".sprintf('%-11s',100*$goodsInfo['price']) ." |#| ".sprintf('%-10s', date("Y-m-d"),time()) ." |#| ".sprintf('%-10s', date('Y-m-d',strtotime('20 year', time()))) ." |#| ".sprintf('%-1s', 0) ." |#| ".sprintf('%-200s', '') ." |#| ".sprintf('%-200s', '') ." |#| ".sprintf('%-200s', '') ." |#| ".sprintf('%-200s', '') ." |#| ".sprintf('%-200s', '') ." |#| ".sprintf('%-200s', '') ." |#| ".sprintf('%-200s', '') ." |#| ".sprintf('%-200s', '') ." |#| ".sprintf('%-200s', '') ." |#| ".sprintf('%-200s', '') ." |#| "." |#| "." |#| "." |#| "."\n";
            if($goodsInfo['type']==2)
            {
                $goodsoutputline.= sprintf('%-11s','WSHHY'. $v['goods_code'])." |#| ".$this->filling_str($goodsInfo['name'],40)." |#| "."03"." |#| "."0210"." |#| ".sprintf('%-10s', 'MSHHY00002') ." |#| ".sprintf('%-11s', '') ." |#| ".sprintf('%-11s',$goodsInfo['integral']) ." |#| ".sprintf('%-10s', date("Y-m-d"),time()) ." |#| ".sprintf('%-10s', date("Y-m-d",$goodsInfo['expire'])) ." |#| ".sprintf('%-1s', 0) ." |#| ".$this->filling_str(trim($goodsInfo['description1']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description2']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description3']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description4']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description5']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description6']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description7']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description8']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description9']),200)." |#| ".$this->filling_str(trim($goodsInfo['description10']),200) ." |#| "." |#| "." |#| "." |#| "."\n";
            }elseif($goodsInfo['type']==3){
                $goodsoutputline.= sprintf('%-11s','WSHHY'. $v['goods_code'])." |#| ".$this->filling_str($goodsInfo['name'],40)." |#| "."03"." |#| "."0210"." |#| ".sprintf('%-10s', 'MSHHY00002') ." |#| ".sprintf('%-11s', $goodsInfo['price']) ." |#| ".sprintf('%-11s',$goodsInfo['integral']) ." |#| ".sprintf('%-10s', date("Y-m-d"),time()) ." |#| ".sprintf('%-10s', date("Y-m-d",$goodsInfo['expire'])) ." |#| ".sprintf('%-1s', 0) ." |#| ".$this->filling_str(trim($goodsInfo['description1']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description2']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description3']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description4']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description5']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description6']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description7']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description8']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description9']),200)." |#| ".$this->filling_str(trim($goodsInfo['description10']),200) ." |#| "." |#| "." |#| "." |#| "."\n";
            }else {
                $goodsoutputline.= sprintf('%-11s','WSHHY'. $v['goods_code'])." |#| ".$this->filling_str($goodsInfo['name'],40)." |#| "."03"." |#| "."0210"." |#| ".sprintf('%-10s', 'MSHHY00002') ." |#| ".sprintf('%-11s', $goodsInfo['price']) ." |#| ".sprintf('%-11s','') ." |#| ".sprintf('%-10s', date("Y-m-d"),time()) ." |#| ".sprintf('%-10s', date("Y-m-d",$goodsInfo['expire'])) ." |#| ".sprintf('%-1s', 0) ." |#| ".$this->filling_str(trim($goodsInfo['description1']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description2']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description3']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description4']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description5']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description6']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description7']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description8']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description9']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description10']),200) ." |#| "." |#| "." |#| "." |#| "."\n";
            }
            $goods_pic_file=$this->httpcopy($goodsInfo['pic_url'],'WSHHY'. $v['goods_code'],DOC_ROOT . "/Uploads/Boccoupon/Download/",$timeout=60);
            \Think\Log::write('httpcopy：'.$goods_pic_file,'INFO');
            if($goods_pic_file)
            {
                $this->addzip($bocconfig['upload'] . $picFileName. '.ZIP',$goods_pic_file,sprintf('%-11s','WSHHY'. $v['goods_code']).'.'.substr(strrchr($goods_pic_file, '.'), 1));
            }

        }
        $goodsoutputline.='TLRL'. sprintf('%015s', count($new_goods));

        file_put_contents($bocconfig['upload'].$goodsFileName,mb_convert_encoding($goodsoutputline,'GBK',mb_detect_encoding($goodsoutputline,array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'))));

        if (PATH_SEPARATOR==':') {
            exec(" cd {$bocconfig['upload']} && zip -j {$goodsFileName}.ZIP  {$goodsFileName}", $output);
        } else {
            $this->zip($bocconfig['upload'] . $goodsFileName. '.ZIP', $bocconfig['upload'] . $goodsFileName);
        }
        if(file_exists($bocconfig['upload'] .  $goodsFileName . '.ZIP')) {
            if (PATH_SEPARATOR==':') {
                //$task="java -version";
                $task="cd ". DOC_ROOT . "/Uploads/Boccoupon/Library/" . ";java -jar PgpEncryUtil.jar ". $bocconfig['upload'] .  $goodsFileName . '.ZIP ' . $bocconfig['upload'] .  $goodsFileName . '.ZIP.DAT ' . DOC_ROOT . "/Uploads/Boccoupon/Cert/dsfpublic.asc";
            } else {
                $task = substr(DOC_ROOT, 0, 2) . "&cd " . DOC_ROOT . "/Uploads/Boccoupon/Library/" . "&java -jar PgpEncryUtil.jar " . $bocconfig['upload'] .  $goodsFileName . '.ZIP ' . $bocconfig['upload'] .  $goodsFileName . '.ZIP.DAT ' . DOC_ROOT . "/Uploads/Boccoupon/Cert/dsfpublic.asc";
            }
        }
        exec( $task." 2>&1",$out);//
        if($out[0]!='success')
        {
            $this->error('加密商品文件失败！',U('/Admin/Purchase/index'));
            exit;
        }
        $merchant_pic=DOC_ROOT.'/Uploads/Boccoupon/Merchant/MSHHY00002.png';

        $this->addzip($bocconfig['upload'] . $picFileName. '.ZIP',$merchant_pic,"PSHHY".sprintf('%05s', 2).'.png');
        if(file_exists($bocconfig['upload'] .  $picFileName . '.ZIP')) {
            if (PATH_SEPARATOR==':') {
                $task="cd ". DOC_ROOT . "/Uploads/Boccoupon/Library/" . ";java -jar PgpEncryUtil.jar ". $bocconfig['upload'] . $picFileName . '.ZIP ' . $bocconfig['upload'] .  $picFileName . '.ZIP.DAT ' . DOC_ROOT . "/Uploads/Boccoupon/Cert/dsfpublic.asc";
            } else {
                $task = substr(DOC_ROOT, 0, 2) . "&cd " . DOC_ROOT . "/Uploads/Boccoupon/Library/" . "&java -jar PgpEncryUtil.jar " . $bocconfig['upload'] .  $picFileName . '.ZIP ' . $bocconfig['upload'] .  $picFileName . '.ZIP.DAT ' . DOC_ROOT . "/Uploads/Boccoupon/Cert/dsfpublic.asc";

            }
        }
        exec( $task." 2>&1",$out);//
        \Think\Log::write('json_data：'.$task,'INFO');
        if($out[0]!='success')
        {
            $this->error('加密图片文件失败！',U('/Admin/Purchase/index'));
        }

        $merchantFileName="MER.SHHY.".date("Ymd").".00.P";
        $merchantputline= sprintf('%-10s','MSHHY00002')." |#| ".sprintf('%-6s', '000000')." |#| ".sprintf('%-6s', '000000')." |#| ".sprintf('%-6s', '000000')." |#| ".$this->filling_str('海牙湾国际有限公司',200)  ." |#| ".$this->filling_str('海牙湾国际有限公司  G-Town International Limited ',200)  ." |#| ".$this->filling_str('10年来专注客户研究，力求比客户更加了解客户。我们视客户为合作伙伴，共同致力于品牌忠诚度计划解决方案,并实时创造品牌专属衍生品。我们讨厌沉闷，拒绝平庸。多年来专注化妆品、银行行业相关营销采购服务',200) ." |#| ".$this->filling_str('上海市杨浦区政立路415号中航天盛广场A座9楼 ',200) ." |#| ".sprintf('%-20s', '086-021-51696195')  ." |#| ".sprintf('%-100s','')  ." |#| ".sprintf('%-100s', '')  ." |#| ".sprintf('%-50s', '')  ." |#| ".sprintf('%-50s', '') ." |#| ".sprintf('%-10s', "PSHHY".sprintf('%05s',2)) ." |#| "."Y"." |#| ".sprintf('%-15s', 'MSHHY0000000002') ." |#| "." |#| "." |#| "."\n";
        $merchantputline.='TLRL000000000000001';
        //echo php_uname('s');
        file_put_contents($bocconfig['upload'].$merchantFileName,mb_convert_encoding($merchantputline,'GBK',mb_detect_encoding($merchantputline,array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'))));
        //exit;
        if (PATH_SEPARATOR==':') {
            exec(" cd {$bocconfig['upload']} && zip -j {$merchantFileName}.ZIP  {$merchantFileName}", $output);
        } else {
            // echo 888;exit;
            $this->zip($bocconfig['upload'] . $merchantFileName. '.ZIP', $bocconfig['upload'] . $merchantFileName);

        }
        if(file_exists($bocconfig['upload'] .  $merchantFileName . '.ZIP')) {
            if (PATH_SEPARATOR==':') {
                $task="cd ". DOC_ROOT . "/Uploads/Boccoupon/Library/" . ";java -jar PgpEncryUtil.jar ". $bocconfig['upload'] .  $merchantFileName . '.ZIP ' . $bocconfig['upload'] .  $merchantFileName . '.ZIP.DAT ' . DOC_ROOT . "/Uploads/Boccoupon/Cert/dsfpublic.asc";
            } else {
                $task = substr(DOC_ROOT, 0, 2) . "&cd " . DOC_ROOT . "/Uploads/Boccoupon/Library/" . "&java -jar PgpEncryUtil.jar " . $bocconfig['upload'] .  $merchantFileName . '.ZIP ' . $bocconfig['upload'] .  $merchantFileName . '.ZIP.DAT ' . DOC_ROOT . "/Uploads/Boccoupon/Cert/dsfpublic.asc";

            }
        }

        exec( $task." 2>&1",$out);//
        if($out[0]=='success')
        {
            exit;
            if (PATH_SEPARATOR==':') {
                $task = "cd " . DOC_ROOT . "/Uploads/Boccoupon/Library/SFTP/" . ";java -jar Sftpupload_fat.jar /upload " . $bocconfig['upload'] . $goodsFileName . '.ZIP.DAT';
            } else {
                $task = substr(DOC_ROOT, 0, 2) . "&cd " . DOC_ROOT . "/Uploads/Boccoupon/Library/SFTP/" . "&java -jar  Sftpupload_fat.jar /upload " . $bocconfig['upload'] . $goodsFileName . '.ZIP.DAT';
            }

            exec($task . " 2>&1", $out);
            //echo $task;exit;
            if ($out[0] == 'success') {
                if (PATH_SEPARATOR==':') {
                    $task = "cd " . DOC_ROOT . "/Uploads/Boccoupon/Library/SFTP/" . ";java -jar Sftpupload_fat.jar /upload " . $bocconfig['upload'] . $filename . '.ZIP.DAT';
                } else {
                    $task = substr(DOC_ROOT, 0, 2) . "&cd " . DOC_ROOT . "/Uploads/Boccoupon/Library/SFTP/" . "&java -jar  Sftpupload_fat.jar /upload " . $bocconfig['upload'] . $filename . '.ZIP.DAT';
                }
                exec($task . " 2>&1", $out);
                if ($out[0] == 'success') {
                    if (PATH_SEPARATOR==':') {
                        $task = "cd " . DOC_ROOT . "/Uploads/Boccoupon/Library/SFTP/" . ";java -jar Sftpupload_fat.jar /upload " . $bocconfig['upload'] . $picFileName . '.ZIP.DAT';
                    } else {
                        $task = substr(DOC_ROOT, 0, 2) . "&cd " . DOC_ROOT . "/Uploads/Boccoupon/Library/SFTP/" . "&java -jar  Sftpupload_fat.jar /upload " . $bocconfig['upload'] . $picFileName . '.ZIP.DAT';
                    }
                    exec($task . " 2>&1", $out);
                    if ($out[0] == 'success') {
                        if (PATH_SEPARATOR==':') {
                            $task = "cd " . DOC_ROOT . "/Uploads/Boccoupon/Library/SFTP/" . ";java -jar Sftpupload_fat.jar /upload " . $bocconfig['upload'] . $merchantFileName . '.ZIP.DAT';
                        } else {
                            $task = substr(DOC_ROOT, 0, 2) . "&cd " . DOC_ROOT . "/Uploads/Boccoupon/Library/SFTP/" . "&java -jar  Sftpupload_fat.jar /upload " . $bocconfig['upload'] . $merchantFileName . '.ZIP.DAT';
                        }
                        exec($task . " 2>&1", $out);
                        if ($out[0] == 'success') {

                            M('batch_log')->add(array('batch_no'=>$batch_no,'status'=>1,'updatetime'=>time()));
                            $this->success('上传成功', U('/Admin/bocvirtualcard/index'));

                        } else {
                            $this->error('上传商户文件失败', U('/Admin/Purchase/index'));
                        }

                    } else {
                        $this->error('上传图片文件失败', U('/Admin/Purchase/index'));
                    }

                } else {
                    $this->error('上传卡券文件失败', U('/Admin/Purchase/index'));
                }

            } else {
                $this->error('上传商品文件失败', U('/Admin/Purchase/index'));
            }


        }

    }


    public function export1()
    {
        $batch_no="BFSH_".date("Y-m-d");

        $bocconfig = C('BOC_COUPON_CONFIG');
        $bocconfig['upload'] = DOC_ROOT . $bocconfig['upload'] . "/" . $batch_no . "/";
        if (!file_exists($bocconfig['upload'])) {
            mkdir($bocconfig['upload'], 0777, true);
        }
        $outputline='';
        $outputline.='TLRL'. sprintf('%015s', 0);
        $filename = 'COUPON' . '.SHHY.'. date('Ymd').'.00'.'.P';
        file_put_contents($bocconfig['upload'].$filename,mb_convert_encoding($outputline,'GBK',mb_detect_encoding($outputline,array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'))));
        if (PATH_SEPARATOR==':') {
            exec(" cd {$bocconfig['upload']} && zip -j {$filename}.ZIP  {$filename}", $output);
        } else {
            $this->zip($bocconfig['upload'] . $filename . '.ZIP', $bocconfig['upload'] . $filename);
        }
        if(file_exists($bocconfig['upload'] . $filename . '.ZIP')) {
            if (PATH_SEPARATOR==':') {
                $task="cd ". DOC_ROOT . "/Uploads/Boccoupon/Library/" . ";java -jar PgpEncryUtil.jar ". $bocconfig['upload'] . $filename . '.ZIP ' . $bocconfig['upload'] . $filename . '.ZIP.DAT ' . DOC_ROOT . "/Uploads/Boccoupon/Cert/dsfpublic.asc";
            } else {
                $task = substr(DOC_ROOT, 0, 2) . "&cd " . DOC_ROOT . "/Uploads/Boccoupon/Library/" . "&java -jar PgpEncryUtil.jar " . $bocconfig['upload'] . $filename . '.ZIP ' . $bocconfig['upload'] . $filename . '.ZIP.DAT ' . DOC_ROOT . "/Uploads/Boccoupon/Cert/dsfpublic.asc";

            }
        }

        exec( $task." 2>&1",$out);//

        if($out[0]!='success')
        {
            $this->error('加密卡券文件失败',U('/Admin/Purchase/index'));
        }
        $goodsFileName="WARES.SHHY.".date("Ymd").".00.P";
        $picFileName="PIC.SHHY.".date("Ymd").".00.P";
        $goodsoutputline='';


        $goodsoutputline.='TLRL'. sprintf('%015s', 0);

        

        file_put_contents($bocconfig['upload'].$goodsFileName,mb_convert_encoding($goodsoutputline,'GBK',mb_detect_encoding($goodsoutputline,array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'))));

        if (PATH_SEPARATOR==':') {
            exec(" cd {$bocconfig['upload']} && zip -j {$goodsFileName}.ZIP  {$goodsFileName}", $output);
        } else {
            $this->zip($bocconfig['upload'] . $goodsFileName. '.ZIP', $bocconfig['upload'] . $goodsFileName);
        }
        if(file_exists($bocconfig['upload'] .  $goodsFileName . '.ZIP')) {
            if (PATH_SEPARATOR==':') {
                //$task="java -version";
                $task="cd ". DOC_ROOT . "/Uploads/Boccoupon/Library/" . ";java -jar PgpEncryUtil.jar ". $bocconfig['upload'] .  $goodsFileName . '.ZIP ' . $bocconfig['upload'] .  $goodsFileName . '.ZIP.DAT ' . DOC_ROOT . "/Uploads/Boccoupon/Cert/dsfpublic.asc";
            } else {
                $task = substr(DOC_ROOT, 0, 2) . "&cd " . DOC_ROOT . "/Uploads/Boccoupon/Library/" . "&java -jar PgpEncryUtil.jar " . $bocconfig['upload'] .  $goodsFileName . '.ZIP ' . $bocconfig['upload'] .  $goodsFileName . '.ZIP.DAT ' . DOC_ROOT . "/Uploads/Boccoupon/Cert/dsfpublic.asc";
            }
        }
        exec( $task." 2>&1",$out);//
        if($out[0]!='success')
        {
            $this->error('加密商品文件失败！',U('/Admin/Purchase/index'));
            exit;
        }
        $merchant_pic=DOC_ROOT.'/Uploads/Boccoupon/Merchant/MSHHY00002.png';

        $this->addzip($bocconfig['upload'] . $picFileName. '.ZIP',$merchant_pic,"PSHHY".sprintf('%05s', 2).'.png');
        if(file_exists($bocconfig['upload'] .  $picFileName . '.ZIP')) {
            if (PATH_SEPARATOR==':') {
                $task="cd ". DOC_ROOT . "/Uploads/Boccoupon/Library/" . ";java -jar PgpEncryUtil.jar ". $bocconfig['upload'] . $picFileName . '.ZIP ' . $bocconfig['upload'] .  $picFileName . '.ZIP.DAT ' . DOC_ROOT . "/Uploads/Boccoupon/Cert/dsfpublic.asc";
            } else {
                $task = substr(DOC_ROOT, 0, 2) . "&cd " . DOC_ROOT . "/Uploads/Boccoupon/Library/" . "&java -jar PgpEncryUtil.jar " . $bocconfig['upload'] .  $picFileName . '.ZIP ' . $bocconfig['upload'] .  $picFileName . '.ZIP.DAT ' . DOC_ROOT . "/Uploads/Boccoupon/Cert/dsfpublic.asc";

            }
        }
        exec( $task." 2>&1",$out);//
        \Think\Log::write('json_data：'.$task,'INFO');
        if($out[0]!='success')
        {
            $this->error('加密图片文件失败！',U('/Admin/Purchase/index'));
        }

        $merchantFileName="MER.SHHY.".date("Ymd").".00.P";
        $merchantputline= sprintf('%-10s','MSHHY00002')." |#| ".sprintf('%-6s', '000000')." |#| ".sprintf('%-6s', '000000')." |#| ".sprintf('%-6s', '000000')." |#| ".$this->filling_str('海牙湾国际有限公司',200)  ." |#| ".$this->filling_str('海牙湾国际有限公司  G-Town International Limited ',200)  ." |#| ".$this->filling_str('10年来专注客户研究，力求比客户更加了解客户。我们视客户为合作伙伴，共同致力于品牌忠诚度计划解决方案,并实时创造品牌专属衍生品。我们讨厌沉闷，拒绝平庸。多年来专注化妆品、银行行业相关营销采购服务',200) ." |#| ".$this->filling_str('上海市杨浦区政立路415号中航天盛广场A座9楼 ',200) ." |#| ".sprintf('%-20s', '086-021-51696195')  ." |#| ".sprintf('%-100s','')  ." |#| ".sprintf('%-100s', '')  ." |#| ".sprintf('%-50s', '')  ." |#| ".sprintf('%-50s', '') ." |#| ".sprintf('%-10s', "PSHHY".sprintf('%05s',2)) ." |#| "."Y"." |#| ".sprintf('%-15s', 'MSHHY0000000002') ." |#| "." |#| "." |#| "."\n";
        $merchantputline.='TLRL000000000000001';
        //echo php_uname('s');
        file_put_contents($bocconfig['upload'].$merchantFileName,mb_convert_encoding($merchantputline,'GBK',mb_detect_encoding($merchantputline,array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'))));
        //exit;
        if (PATH_SEPARATOR==':') {
            exec(" cd {$bocconfig['upload']} && zip -j {$merchantFileName}.ZIP  {$merchantFileName}", $output);
        } else {
            // echo 888;exit;
            $this->zip($bocconfig['upload'] . $merchantFileName. '.ZIP', $bocconfig['upload'] . $merchantFileName);

        }
        if(file_exists($bocconfig['upload'] .  $merchantFileName . '.ZIP')) {
            if (PATH_SEPARATOR==':') {
                $task="cd ". DOC_ROOT . "/Uploads/Boccoupon/Library/" . ";java -jar PgpEncryUtil.jar ". $bocconfig['upload'] .  $merchantFileName . '.ZIP ' . $bocconfig['upload'] .  $merchantFileName . '.ZIP.DAT ' . DOC_ROOT . "/Uploads/Boccoupon/Cert/dsfpublic.asc";
            } else {
                $task = substr(DOC_ROOT, 0, 2) . "&cd " . DOC_ROOT . "/Uploads/Boccoupon/Library/" . "&java -jar PgpEncryUtil.jar " . $bocconfig['upload'] .  $merchantFileName . '.ZIP ' . $bocconfig['upload'] .  $merchantFileName . '.ZIP.DAT ' . DOC_ROOT . "/Uploads/Boccoupon/Cert/dsfpublic.asc";

            }
        }

        exec( $task." 2>&1",$out);//
        if($out[0]=='success')
        {
            if (PATH_SEPARATOR==':') {
                $task = "cd " . DOC_ROOT . "/Uploads/Boccoupon/Library/SFTP/" . ";java -jar Sftpupload_fat.jar /upload " . $bocconfig['upload'] . $goodsFileName . '.ZIP.DAT';
            } else {
                $task = substr(DOC_ROOT, 0, 2) . "&cd " . DOC_ROOT . "/Uploads/Boccoupon/Library/SFTP/" . "&java -jar  Sftpupload_fat.jar /upload " . $bocconfig['upload'] . $goodsFileName . '.ZIP.DAT';
            }

            exec($task . " 2>&1", $out);
            //echo $task;exit;
            if ($out[0] == 'success') {
                if (PATH_SEPARATOR==':') {
                    $task = "cd " . DOC_ROOT . "/Uploads/Boccoupon/Library/SFTP/" . ";java -jar Sftpupload_fat.jar /upload " . $bocconfig['upload'] . $filename . '.ZIP.DAT';
                } else {
                    $task = substr(DOC_ROOT, 0, 2) . "&cd " . DOC_ROOT . "/Uploads/Boccoupon/Library/SFTP/" . "&java -jar  Sftpupload_fat.jar /upload " . $bocconfig['upload'] . $filename . '.ZIP.DAT';
                }
                exec($task . " 2>&1", $out);
                if ($out[0] == 'success') {
                    if (PATH_SEPARATOR==':') {
                        $task = "cd " . DOC_ROOT . "/Uploads/Boccoupon/Library/SFTP/" . ";java -jar Sftpupload_fat.jar /upload " . $bocconfig['upload'] . $picFileName . '.ZIP.DAT';
                    } else {
                        $task = substr(DOC_ROOT, 0, 2) . "&cd " . DOC_ROOT . "/Uploads/Boccoupon/Library/SFTP/" . "&java -jar  Sftpupload_fat.jar /upload " . $bocconfig['upload'] . $picFileName . '.ZIP.DAT';
                    }
                    exec($task . " 2>&1", $out);
                    if ($out[0] == 'success') {
                        if (PATH_SEPARATOR==':') {
                            $task = "cd " . DOC_ROOT . "/Uploads/Boccoupon/Library/SFTP/" . ";java -jar Sftpupload_fat.jar /upload " . $bocconfig['upload'] . $merchantFileName . '.ZIP.DAT';
                        } else {
                            $task = substr(DOC_ROOT, 0, 2) . "&cd " . DOC_ROOT . "/Uploads/Boccoupon/Library/SFTP/" . "&java -jar  Sftpupload_fat.jar /upload " . $bocconfig['upload'] . $merchantFileName . '.ZIP.DAT';
                        }
                        exec($task . " 2>&1", $out);
                        if ($out[0] == 'success') {

                            M('batch_log')->add(array('batch_no'=>$batch_no,'status'=>1,'updatetime'=>time()));
                            $this->success('上传成功', U('/Admin/bocvirtualcard/index'));

                        } else {
                            $this->error('上传商户文件失败', U('/Admin/Purchase/index'));
                        }

                    } else {
                        $this->error('上传图片文件失败', U('/Admin/Purchase/index'));
                    }

                } else {
                    $this->error('上传卡券文件失败', U('/Admin/Purchase/index'));
                }

            } else {
                $this->error('上传商品文件失败', U('/Admin/Purchase/index'));
            }


        }

    }
        //print_r( $orderList);exit;
        //$orderList=M('boc_order')  ->where('goods_id='.$v['id']." and cstatus=1 and expire>".time())->count();



    private  function createSign($appkey,$array){
        ksort($array);
        $str = '';
        foreach ($array as $key=>$va){
            $str.=$key.$va;
        }
        $str.=$appkey;

        return md5($str);
    }



    private  function is_json($string) {
       json_decode($string);
       return (json_last_error() == JSON_ERROR_NONE);
    }

    function getHttpContent($url, $method = 'GET', $postData = array())
    {
        $data = '';
        if (!empty($url)) {
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30); //30秒超时
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                //curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_jar);
                if (strtoupper($method) == 'POST') {
                    $curlPost = is_array($postData) ? http_build_query($postData) : $postData;
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
                }
                $data = curl_exec($ch);
                curl_close($ch);
            } catch (Exception $e) {
                $data = null;
            }
        }
        return json_decode($data,true);
    }

    /**
     *
     * @param        $url
     * @param string $method
     * @param array  $postData
     *
     * @return mixed|null|string
     */
    private function getHttp($url,$postData)
    {
        $data = '';
        if (!empty($url)) {
            try {
               $headers = array("Content-type:application/json");
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt ( $ch, CURLOPT_POST, 1 );
                curl_setopt($ch, CURLOPT_TIMEOUT, 60); //30秒超时
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt ( $ch, CURLOPT_POSTFIELDS,$postData);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                $data = curl_exec($ch);
                var_dump($data);
                curl_close($ch);
            } catch (Exception $e) {
                $data = null;
            }
        }
        //var_dump($data);
       // ob_end_clean();
        return json_decode($data,true);
    }


    private function checkSign($sign,$array){

        ksort($array);
        $str = '';
        foreach ($array as $key=>$va){
            if($va)
                $str.=$key.$va;
        }
        $str.="gtownboc365gtown";
        $str =  md5($str);
        if($sign!=$str) return false;
        return true;
    }


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

    private function addzip($zipname,$fileToZip,$newFileName){
        $zip = new ZipArchive();
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


    private  function urlsafe_b64encode($string) {
        $data = base64_encode($string);
        \Think\Log::write('卡券$string：'. $data,'INFO');
        $data = str_replace(array('+','/','='),array('-','_',''),$data);
        return $data;
    }


}




//function checksign(){
//
//    $error = ['code'=>-1,'msg'=>''];
//    if(!isset($this->params['sign'])){
//        $error['msg'] = '缺少[sign]参数';
//        $this->error= $error;
//        return ;
//    }
//    $sign = $this->params['sign'];
//    unset($this->params['sign']);
//    $appkey = "gtownboc365gtown";
//    $check = BCoupon::instance()->checkSign($appkey, $sign, $this->params);
//
//    if(!$check){
//        $error['msg'] = '签名错误';
//        $this->error = $error;
//    }
//
//}