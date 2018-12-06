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
class PurchaseController extends AdminController
{
    /***商品列表***/
    public function index()
    {
        //echo 999;exit;

        $nowPage    = empty(I('p'))? 1 : intval(I('p'));
        $nowPage    = $nowPage>0 ? $nowPage : 1;
//        $map=1;
        $table=M('boc_order');
        $nums= $table->where("batch_no like 'BFSH_%'")->field("count(*) as count,batch_no")->group("batch_no")->select();// 查询满足要求的总记录数
        $count=count($nums);
        import("@.ORG.Util.AjaxPage");
        $Page =new \AjaxPage($nowPage,$count,'ajax-page',10);// 实例化分页类 传入总记录数、ajax更新的局部页面ID和每页显示的记录数(10)
        $Page->lastSuffix=false;
        $Page->setConfig('first','首页');
        $Page->setConfig('last','末页');
        $Page->setConfig('header','<span class="rows btn btn-default margin-l-2">共 %TOTAL_ROW% 条</span>');//分页条数
        $Page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');//分页样式：首页、末页等
        $show= $Page->show();// 分页显示输出
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        //SELECT COUNT(*) AS total,`batch_no`  FROM  `vrc_boc_order` GROUP  BY `batch_no`
        $datas=$table->where("batch_no like 'BFSH_%'")->field("count(*) as count,batch_no")->group("batch_no")->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        foreach($datas as $k=>$v)
        {
            $isupload=M('batch_log')->where("batch_no='".$v['batch_no']."'")->find();
            $datas[$k]['isupload']=$isupload['status'];
        }
        $this->assign('datas',$datas);// 赋值数据集
        $this->assign('page',$show);// 赋值分页输出
        if(IS_AJAX){
            $this->display('index_ajax');
        }else{
            $this->display();
        }
    }


    public function batchDetail()
    {
        $res=array("state"=>200,"list"=>array());
        $batchNo = trim(I('batchNo'));
        //$batchNo='2018-05-23';
        $table=M('boc_order');
        $list=$table->where("batch_no='".$batchNo."'")->field("count(*) as count,goods_id")->group("goods_id")->select();
        if(empty($list))
        {
            $res=201;
        }
        foreach ($list as $k=>$v)
        {
            $goods = M("goods")->where("id='".$v['goods_id']."'")->field('name,id,type,sequence,CASE `type`  WHEN 2  THEN "积分券" ELSE "现金券" END AS goods_type')->find();
            $list[$k]['goods']=$goods;

        }
        $res['list']=$list;
        echo json_encode($res);exit;
    }
    public function export()
    {
        $platform=I('platform');
        $model = M("boc_order");
        $bocconfig = C('BOC_COUPON_CONFIG');
        $outputline='';
        $new_goods=array();
        $batch_no=trim(I('batch_no'));
        $bocconfig['upload'] = DOC_ROOT . $bocconfig['upload'] . "/" . $batch_no . "/";
        $isupload=M('batch_log')->where("batch_no='".$batch_no."'")->find();
        if($isupload['status']==1)
        {
            $this->error('批次已经上传了',U('/Admin/Purchase/index'));
        }
        if (!file_exists($bocconfig['upload'])) {
            mkdir($bocconfig['upload'], 0777, true);
        }
        $couponList=$model->where("batch_no='".$batch_no."'")->select();
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
                $goodsoutputline.= sprintf('%-11s','WSHHY'. $v['goods_code'])." |#| ".$this->filling_str($goodsInfo['name'],40)." |#| "."03"." |#| "."0210"." |#| ".sprintf('%-10s', 'MSHHY00001') ." |#| ".sprintf('%-11s', '') ." |#| ".sprintf('%-11s',$goodsInfo['integral']) ." |#| ".sprintf('%-10s', date("Y-m-d"),time()) ." |#| ".sprintf('%-10s', date("Y-m-d",$goodsInfo['expire'])) ." |#| ".sprintf('%-1s', 0) ." |#| ".$this->filling_str(trim($goodsInfo['description1']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description2']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description3']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description4']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description5']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description6']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description7']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description8']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description9']),200)." |#| ".$this->filling_str(trim($goodsInfo['description10']),200) ." |#| "." |#| "." |#| "." |#| "."\n";
            }elseif($goodsInfo['type']==3){
                $goodsoutputline.= sprintf('%-11s','WSHHY'. $v['goods_code'])." |#| ".$this->filling_str($goodsInfo['name'],40)." |#| "."03"." |#| "."0210"." |#| ".sprintf('%-10s', 'MSHHY00001') ." |#| ".sprintf('%-11s', $goodsInfo['price']) ." |#| ".sprintf('%-11s',$goodsInfo['integral']) ." |#| ".sprintf('%-10s', date("Y-m-d"),time()) ." |#| ".sprintf('%-10s', date("Y-m-d",$goodsInfo['expire'])) ." |#| ".sprintf('%-1s', 0) ." |#| ".$this->filling_str(trim($goodsInfo['description1']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description2']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description3']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description4']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description5']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description6']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description7']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description8']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description9']),200)." |#| ".$this->filling_str(trim($goodsInfo['description10']),200) ." |#| "." |#| "." |#| "." |#| "."\n";
            }else {
                $goodsoutputline.= sprintf('%-11s','WSHHY'. $v['goods_code'])." |#| ".$this->filling_str($goodsInfo['name'],40)." |#| "."03"." |#| "."0210"." |#| ".sprintf('%-10s', 'MSHHY00001') ." |#| ".sprintf('%-11s', $goodsInfo['price']) ." |#| ".sprintf('%-11s','') ." |#| ".sprintf('%-10s', date("Y-m-d"),time()) ." |#| ".sprintf('%-10s', date("Y-m-d",$goodsInfo['expire'])) ." |#| ".sprintf('%-1s', 0) ." |#| ".$this->filling_str(trim($goodsInfo['description1']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description2']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description3']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description4']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description5']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description6']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description7']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description8']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description9']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description10']),200) ." |#| "." |#| "." |#| "." |#| "."\n";
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
        $merchant_pic=DOC_ROOT.'/Uploads/Boccoupon/Merchant/MSHHY00001.png';

        $this->addzip($bocconfig['upload'] . $picFileName. '.ZIP',$merchant_pic,"PSHHY".sprintf('%05s', 1).'.png');
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
        $merchantputline= sprintf('%-10s','MSHHY00001')." |#| ".sprintf('%-6s', '310000')." |#| ".sprintf('%-6s', '310100')." |#| ".sprintf('%-6s', '000000')." |#| ".$this->filling_str('海牙湾国际有限公司',200)  ." |#| ".$this->filling_str('海牙湾国际有限公司  G-Town International Limited ',200)  ." |#| ".$this->filling_str('10年来专注客户研究，力求比客户更加了解客户。我们视客户为合作伙伴，共同致力于品牌忠诚度计划解决方案,并实时创造品牌专属衍生品。我们讨厌沉闷，拒绝平庸。多年来专注化妆品、银行行业相关营销采购服务',200) ." |#| ".$this->filling_str('上海市杨浦区政立路415号中航天盛广场A座9楼 ',200) ." |#| ".sprintf('%-20s', '086-021-51696195')  ." |#| ".sprintf('%-100s','')  ." |#| ".sprintf('%-100s', '')  ." |#| ".sprintf('%-50s', '')  ." |#| ".sprintf('%-50s', '') ." |#| ".sprintf('%-10s', "PSHHY".sprintf('%05s', 1)) ." |#| "."Y"." |#| ".sprintf('%-15s', 'MSHHY0000000001') ." |#| "." |#| "." |#| "."\n";
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

    public function bfshProductsexport()
    {
        $platform=I('platform');
        $model = M("boc_order");
        $bocconfig = C('BOC_COUPON_CONFIG');
        $outputline='';
        $new_goods=array();
        $batch_no=trim(I('batch_no'));
        $bocconfig['upload'] = DOC_ROOT . $bocconfig['upload'] . "/" . $batch_no . "/";
        $isupload=M('batch_log')->where("batch_no='".$batch_no."'")->find();
        if($isupload['status']==1)
        {
            $this->error('批次已经上传了',U('/Admin/Purchase/index'));
        }
        if (!file_exists($bocconfig['upload'])) {
            mkdir($bocconfig['upload'], 0777, true);
        }
        $couponList=$model->where("batch_no='".$batch_no."'")->select();
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
                $goodsoutputline.= sprintf('%-11s','WSHHY'. $v['goods_code'])." |#| ".$this->filling_str($goodsInfo['name'],40)." |#| "."03"." |#| "."0210"." |#| ".sprintf('%-10s', 'MSHHY00001') ." |#| ".sprintf('%-11s', '') ." |#| ".sprintf('%-11s',$goodsInfo['integral']) ." |#| ".sprintf('%-10s', date("Y-m-d"),time()) ." |#| ".sprintf('%-10s', date("Y-m-d",$goodsInfo['expire'])) ." |#| ".sprintf('%-1s', 0) ." |#| ".$this->filling_str(trim($goodsInfo['description1']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description2']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description3']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description4']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description5']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description6']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description7']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description8']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description9']),200)." |#| ".$this->filling_str(trim($goodsInfo['description10']),200) ." |#| "." |#| "." |#| "." |#| "."\n";
            }elseif($goodsInfo['type']==3){
                $goodsoutputline.= sprintf('%-11s','WSHHY'. $v['goods_code'])." |#| ".$this->filling_str($goodsInfo['name'],40)." |#| "."03"." |#| "."0210"." |#| ".sprintf('%-10s', 'MSHHY00001') ." |#| ".sprintf('%-11s', $goodsInfo['price']) ." |#| ".sprintf('%-11s',$goodsInfo['integral']) ." |#| ".sprintf('%-10s', date("Y-m-d"),time()) ." |#| ".sprintf('%-10s', date("Y-m-d",$goodsInfo['expire'])) ." |#| ".sprintf('%-1s', 0) ." |#| ".$this->filling_str(trim($goodsInfo['description1']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description2']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description3']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description4']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description5']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description6']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description7']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description8']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description9']),200)." |#| ".$this->filling_str(trim($goodsInfo['description10']),200) ." |#| "." |#| "." |#| "." |#| "."\n";
            }else {
                $goodsoutputline.= sprintf('%-11s','WSHHY'. $v['goods_code'])." |#| ".$this->filling_str($goodsInfo['name'],40)." |#| "."03"." |#| "."0210"." |#| ".sprintf('%-10s', 'MSHHY00001') ." |#| ".sprintf('%-11s', $goodsInfo['price']) ." |#| ".sprintf('%-11s','') ." |#| ".sprintf('%-10s', date("Y-m-d"),time()) ." |#| ".sprintf('%-10s', date("Y-m-d",$goodsInfo['expire'])) ." |#| ".sprintf('%-1s', 0) ." |#| ".$this->filling_str(trim($goodsInfo['description1']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description2']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description3']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description4']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description5']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description6']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description7']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description8']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description9']),200) ." |#| ".$this->filling_str(trim($goodsInfo['description10']),200) ." |#| "." |#| "." |#| "." |#| "."\n";
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
        $merchant_pic=DOC_ROOT.'/Uploads/Boccoupon/Merchant/MSHHY00001.png';

        $this->addzip($bocconfig['upload'] . $picFileName. '.ZIP',$merchant_pic,"PSHHY".sprintf('%05s', 1).'.png');
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
        @copy(DOC_ROOT.'/Uploads/Boccoupon/Merchant/MER_01',$bocconfig['upload'].$merchantFileName);
        //$merchantputline= sprintf('%-10s','MSHHY00001')." |#| ".sprintf('%-6s', '310000')." |#| ".sprintf('%-6s', '310100')." |#| ".sprintf('%-6s', '000000')." |#| ".$this->filling_str('海牙湾国际有限公司',200)  ." |#| ".$this->filling_str('海牙湾国际有限公司  G-Town International Limited ',200)  ." |#| ".$this->filling_str('10年来专注客户研究，力求比客户更加了解客户。我们视客户为合作伙伴，共同致力于品牌忠诚度计划解决方案,并实时创造品牌专属衍生品。我们讨厌沉闷，拒绝平庸。多年来专注化妆品、银行行业相关营销采购服务',200) ." |#| ".$this->filling_str('上海市杨浦区政立路415号中航天盛广场A座9楼 ',200) ." |#| ".sprintf('%-20s', '086-021-51696195')  ." |#| ".sprintf('%-100s','')  ." |#| ".sprintf('%-100s', '')  ." |#| ".sprintf('%-50s', '')  ." |#| ".sprintf('%-50s', '') ." |#| ".sprintf('%-10s', "PSHHY".sprintf('%05s', 1)) ." |#| "."Y"." |#| ".sprintf('%-15s', 'MSHHY0000000001') ." |#| "." |#| "." |#| "."\n";
        //$merchantputline.='TLRL000000000000001';
        //echo php_uname('s');
        //file_put_contents($bocconfig['upload'].$merchantFileName,mb_convert_encoding($merchantputline,'GBK',mb_detect_encoding($merchantputline,array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'))));
        //exit;
        if(file_exists($bocconfig['upload'] .  $merchantFileName)) {
            if (PATH_SEPARATOR == ':') {
                exec(" cd {$bocconfig['upload']} && zip -j {$merchantFileName}.ZIP  {$merchantFileName}", $output);
            } else {
                // echo 888;exit;
                $this->zip($bocconfig['upload'] . $merchantFileName . '.ZIP', $bocconfig['upload'] . $merchantFileName);

            }
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

    public function bcspexport()
   {
     set_time_limit(0);
     @ini_set("max_execution_time", 0);
     $model = M("boc_order");
     $bocconfig = C('BOC_COUPON_CONFIG');
       $outputline='TxnDetailStart'. "\n";
     $batch_no=trim(I('batch_no'));
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
                   $goodsInfo = M('goods g')->join('vrc_bcsp_goods_code c on g.bcsp_goods_code=c.id')->where("g.id = '" . $v['goods_id'] . "'")->field("c.bcsp_goods_code")->find();
                   $outputline .= sprintf('%015s', $v['id']) . "||" . $goodsInfo['bcsp_goods_code'] . "||SHHY_" . sprintf('%015s', $v['id']) . "||" . $encryptData[base64_decode($v['coupon_sn'])] . "||" . "||" . "||" . "||" . "||" . "\n";
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
               //exit;
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
    /***商品列表***/
    public function bcsplist()
    {
        //echo 999;exit;

        $nowPage    = empty(I('p'))? 1 : intval(I('p'));
        $nowPage    = $nowPage>0 ? $nowPage : 1;
//        $map=1;
        $table=M('boc_order');
        $nums= $table->where("batch_no like 'BCSP_%'")->field("count(*) as count,batch_no")->group("batch_no")->select();// 查询满足要求的总记录数
        $count=count($nums);
        import("@.ORG.Util.AjaxPage");
        $Page =new \AjaxPage($nowPage,$count,'ajax-page',10);// 实例化分页类 传入总记录数、ajax更新的局部页面ID和每页显示的记录数(10)
        $Page->lastSuffix=false;
        $Page->setConfig('first','首页');
        $Page->setConfig('last','末页');
        $Page->setConfig('header','<span class="rows btn btn-default margin-l-2">共 %TOTAL_ROW% 条</span>');//分页条数
        $Page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');//分页样式：首页、末页等
        $show= $Page->show();// 分页显示输出
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        //SELECT COUNT(*) AS total,`batch_no`  FROM  `vrc_boc_order` GROUP  BY `batch_no`
        $datas=$table->where("batch_no like 'BCSP_%'")->field("count(*) as count,batch_no")->group("batch_no")->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        foreach($datas as $k=>$v)
        {
            $isupload=M('batch_log')->where("batch_no='".$v['batch_no']."'")->find();
            $datas[$k]['isupload']=$isupload['status'];
        }
        $this->assign('datas',$datas);// 赋值数据集
        $this->assign('page',$show);// 赋值分页输出

        if(IS_AJAX){
            $this->display('bcsp_ajax');
        }else{
            $this->display();
        }
    }


    /***商品列表***/
    public function bfshproductslist()
    {
        //echo 999;exit;

        $nowPage    = empty(I('p'))? 1 : intval(I('p'));
        $nowPage    = $nowPage>0 ? $nowPage : 1;
//        $map=1;

        
        $table=M('boc_order');
        $nums= $table->where("batch_no like 'BFSW_%'")->field("count(*) as count,batch_no")->group("batch_no")->select();// 查询满足要求的总记录数
        $count=count($nums);
        import("@.ORG.Util.AjaxPage");
        $Page =new \AjaxPage($nowPage,$count,'ajax-page',10);// 实例化分页类 传入总记录数、ajax更新的局部页面ID和每页显示的记录数(10)
        $Page->lastSuffix=false;
        $Page->setConfig('first','首页');
        $Page->setConfig('last','末页');
        $Page->setConfig('header','<span class="rows btn btn-default margin-l-2">共 %TOTAL_ROW% 条</span>');//分页条数
        $Page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');//分页样式：首页、末页等
        $show= $Page->show();// 分页显示输出
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        //SELECT COUNT(*) AS total,`batch_no`  FROM  `vrc_boc_order` GROUP  BY `batch_no`
        $datas=$table->where("batch_no like 'BCSP_%'")->field("count(*) as count,batch_no")->group("batch_no")->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        foreach($datas as $k=>$v)
        {
            $isupload=M('batch_log')->where("batch_no='".$v['batch_no']."'")->find();
            $datas[$k]['isupload']=$isupload['status'];
        }
        $this->assign('datas',$datas);// 赋值数据集
        $this->assign('page',$show);// 赋值分页输出

        if(IS_AJAX){
            $this->display('bfshproducts_ajax');
        }else{
            $this->display();
        }
    }
//    private function unzip($path,$zipName){
//        $resource = zip_open($path.$zipName);
//        while ($dir_resource = zip_read($resource)) {
//            $file_content = zip_entry_read($dir_resource,1000000);
//            $name =  zip_entry_name($dir_resource);
//            $res+= file_put_contents($path.$name,$file_content);
//
//        }
//        zip_close($resource);
//        return $res;
//
//    }
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
        if(file_exists($filename))
        {
            return  $filename ;
        }
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

    private  function is_json($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }


}





