<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Home\Controller;
use ZipArchive;

/**
 * 用户控制器
 * 包括用户中心，用户登录及注册
 */
class CliController extends HomeController {

    public function index()
    {
        echo 9999;
    }
	/* 用户中心首页 */

    public function bocreturncoupondownload(){
        Vendor('FTP.Ftp');
        $ftp = new \Ftp(C('BOC_FTP'));
        $bocconfig=C('BOC_COUPON_CONFIG');
        $model = M("boc_order");
        $filename='CCANCEL.BOCSHHY.'.date("Ymd",strtotime("0 day")).".00";
        $bocconfig['download']=DOC_ROOT.$bocconfig['download'];
        $bocconfig['upload']=DOC_ROOT.$bocconfig['upload'];

        if (PATH_SEPARATOR==':') {
            $task = "cd " . DOC_ROOT . "/Uploads/Boccoupon/Library/SFTP/" . ";java  -jar Sftpdownload_fat.jar /down ". $filename . '.ZIP.DAT ' . $bocconfig['download'] ;
        } else {
            $task = substr(DOC_ROOT, 0, 2) . "&cd " . DOC_ROOT . "/Uploads/Boccoupon/Library/SFTP/" . "&java -jar Sftpdownload_fat.jar /down ". $filename . '.ZIP.DAT ' . $bocconfig['download'] ;
        }
        exec( $task,$out);//
        if($out[0]=='success')
        {
            //$res = $ftp->download($filename. '.ZIP.DAT', $bocconfig['download'].$filename. '.ZIP.DAT');
            //echo $filename;
            //print_r($res);exit;
            //if ($res) {
            if (PATH_SEPARATOR==':') {
                    $task="cd ". DOC_ROOT . "/Uploads/Boccoupon/Library/" . ";java -jar PgpDecryUtil.jar ".$bocconfig['bocprivatekeypass']." ". $bocconfig['download'] . $filename . '.ZIP.DAT ' . $bocconfig['download'] . $filename . '.ZIP ' . DOC_ROOT . "/Uploads/Boccoupon/Cert/dealsecret.asc";
                } else {
                    $task = substr(DOC_ROOT, 0, 2) . "&cd " . DOC_ROOT . "/Uploads/Boccoupon/Library/" . "&java -jar PgpDecryUtil.jar " .$bocconfig['bocprivatekeypass']." ". $bocconfig['download'] . $filename . '.ZIP.DAT ' . $bocconfig['download'] . $filename . '.ZIP ' . DOC_ROOT . "/Uploads/Boccoupon/Cert/dealsecret.asc";
                   // java -jar PgpDecryUtil.jar   m1h2q3    D:\\test\\CREMA.BOCYIMA.20160913.00.ZIP.DAT   D:\\test\\CREMA.BOCYIMA.20160913.00.ZIP  D:\\cert\\dealsecret.asc
                }

                exec( $task,$out);//
                if($out[0]=='success')
                {
                    if (PATH_SEPARATOR==':') {
                        /**
                         * 解压压缩包*
                         */
                        // linux 下解压
                        exec("unzip -o ". $bocconfig['download'] . $filename . ".ZIP " . "  -d  ".$bocconfig['download'], $output);

                    } else {

                        $output = $this->unzip($bocconfig['download'],  $filename . '.ZIP');


                    }
                    if($output)
                    {
                        $outputline='';
                        if(file_exists($bocconfig['download'] . $filename.".C")) {
                            $arr = file($bocconfig['download'] . $filename . ".C");
                            $arr_end=array_pop($arr);
                            //$res=array();
                            for($i=0;$i<count($arr);$i++)
                            {
                                $data= explode('|#|',$arr[$i]);
                                $model->where("coupon_sn='".base64_encode(trim($data[4]))."'")->save(array('status'=>2,'out_order_sn'=>trim($data[0]),'lastupdate_time'=>date('Y-m-d', strtotime(trim($data[6])))." ".trim($data[7])));
                                $outputline.= sprintf('%-19s', trim($data[0]))." |#| ".sprintf('%-11s', trim($data[1]))." |#| ".sprintf('%-10s', trim($data[2]))." |#| "."E"." |#| ".sprintf('%-36s',trim($data[4]))." |#| "."Y"." |#| ".sprintf('%-8s', trim(date("Ymd"))) ." |#| ".sprintf('%-8s', trim(date("H:i:s"))) ." |#| ".$this->filling_str("经沟通，该商品可退。",200) ." |#| "." |#| "." |#| "." |#| "."\n";
                            }
                            //$outputline.='TLRL'. sprintf('%015s', count($couponList));
                            $outputline.=$arr_end;
                            $upFileName="CCANCEL.SHHY.".date("Ymd",strtotime("+1 day")).".00.C";
                            file_put_contents($bocconfig['upload'].$upFileName,mb_convert_encoding($outputline,'GBK',mb_detect_encoding($outputline,array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'))));

                            if (PATH_SEPARATOR==':') {
                                exec(" cd {$bocconfig['upload']} && zip -j {$upFileName}.ZIP  {$upFileName}", $output);
                            } else {
                                $this->zip($bocconfig['upload'] . $upFileName. '.ZIP', $bocconfig['upload'] . $upFileName);
                            }
                            if(file_exists($bocconfig['upload'] .  $upFileName . '.ZIP')) {
                                if (PATH_SEPARATOR==':') {
                                    $task="cd ". DOC_ROOT . "/Uploads/Boccoupon/Library/" . ";java -jar PgpEncryUtil.jar ". $bocconfig['upload'] .  $upFileName . '.ZIP ' . $bocconfig['upload'] .  $upFileName . '.ZIP.DAT ' . DOC_ROOT . "/Uploads/Boccoupon/Cert/dsfpublic.asc";
                                    file_put_contents("/logs/excu.log", $task);
                                } else {
                                    $task = substr(DOC_ROOT, 0, 2) . "&cd " . DOC_ROOT . "/Uploads/Boccoupon/Library/" . "&java -jar PgpEncryUtil.jar " . $bocconfig['upload'] .  $upFileName . '.ZIP ' . $bocconfig['upload'] .  $upFileName . '.ZIP.DAT ' . DOC_ROOT . "/Uploads/Boccoupon/Cert/dsfpublic.asc";

                                }
                            }
                            exec( $task." 2>&1",$out);//
                            if($out[0]!='success')
                            {
                                echo "加密退货结果文件失败";
                            }else{
//                                Vendor('FTP.Ftp');
//                                $ftp = new \Ftp(C('BOC_FTP'));
//                                $conn = $ftp->sftp_connect();
//                                if ($conn) {
//                                    try {
//                                        $ftp->uploade( $bocconfig['upload'] . $upFileName . '.ZIP.DAT', "/boccopon/upload/" . $upFileName . '.ZIP.DAT');
//                                    } catch (Exception $e){
//                                        $err[]=$e->getMessage();
//                                    }
//                                    if(count($err)>0)
//                                    {
//                                        echo "上传退货文件失败";
//                                    }else{
//
//                                        echo 'success';
//                                    }
//
//                                }else{
//                                    echo "ftp上传退货文件失败";
//                                }
                            }


                        }

                    }else{
                        echo "解压失败";
                    }


                }else{
                    echo "文件解密失败";
                }

            }

    }

    public function  coupon_used()
    {
        //Vendor('FTP.Ftp');
        Vendor('Util.RsaUtil');
        $rsaObject=new \RsaUtil();
        //$ftp = new \Ftp(C('BOC_FTP'));
        $bocconfig=C('BOC_COUPON_CONFIG');
        $filename='CSELL.BOCSHHY.'.date("Ymd",strtotime("-1 day")).".00";
        //$filename="CSELL.BOCSHHY.20171212.00";
        //$conn = $ftp->sftp_connect();
        $bocconfig['download']=DOC_ROOT.$bocconfig['download'];
        $bocconfig['upload']=DOC_ROOT.$bocconfig['upload'];
        //echo $filename;exit;
        if (PATH_SEPARATOR==':') {
            $task = "cd " . DOC_ROOT . "/Uploads/Boccoupon/Library/SFTP/" . ";java  -jar Sftpdownload_fat.jar /down ". $filename . '.ZIP.DAT ' . $bocconfig['download'] ;
        } else {
            $task = substr(DOC_ROOT, 0, 2) . "&cd " . DOC_ROOT . "/Uploads/Boccoupon/Library/SFTP/" . "&java -jar Sftpdownload_fat.jar /down ". $filename . '.ZIP.DAT ' . $bocconfig['download'] ;
        }
        exec( $task,$out);//
        echo $task;
        //print_r($out);exit;
        if($out[0]=='success')
        {
            if (PATH_SEPARATOR==':') {
                    $task="cd ". DOC_ROOT . "/Uploads/Boccoupon/Library/" . ";java -jar PgpDecryUtil.jar ".$bocconfig['bocprivatekeypass']." ". $bocconfig['download'] . $filename . '.ZIP.DAT ' . $bocconfig['download'] . $filename . '.ZIP ' . DOC_ROOT . "/Uploads/Boccoupon/Cert/dealsecret.asc";
                } else {
                    $task = substr(DOC_ROOT, 0, 2) . "&cd " . DOC_ROOT . "/Uploads/Boccoupon/Library/" . "&java -jar PgpDecryUtil.jar " .$bocconfig['bocprivatekeypass']." ". $bocconfig['download'] . $filename . '.ZIP.DAT ' . $bocconfig['download'] . $filename . '.ZIP ' . DOC_ROOT . "/Uploads/Boccoupon/Cert/dealsecret.asc";
                    // java -jar PgpDecryUtil.jar   m1h2q3    D:\\test\\CREMA.BOCYIMA.20160913.00.ZIP.DAT   D:\\test\\CREMA.BOCYIMA.20160913.00.ZIP  D:\\cert\\dealsecret.asc
                }
                exec( $task,$out);//
                //$out[0]='success';
                //print_r($out);exit;
                if($out[0]=='success')
                {
                    if (PATH_SEPARATOR==':') {
                        /**
                         * 解压压缩包*
                         */
                        // linux 下解压
                        exec("unzip -o ". $bocconfig['download'] . $filename . ".ZIP " . "  -d  ".$bocconfig['download'], $output);

                    } else {


                        $output = $this->unzip($bocconfig['download'],  $filename . '.ZIP');


                    }

                    //$output=true;
                    if($output)
                    {

                        $outputline='';

                        if(file_exists($bocconfig['download'] . $filename.".B")) {
                            $arr = file($bocconfig['download'] . $filename . ".B");
                            $arr_end=array_pop($arr);
                            $use_num=0;
                            $orderList=array();
                            for($i=0;$i<count($arr);$i++)
                            {
                                $data= explode('|#|',$arr[$i]);
                                //echo base64_encode(trim($data[4]))."<br>";
                                $return_num=M('boc_order')->where("coupon_sn='".base64_encode(trim($data[4]))."' and status=2 and out_order_sn='".trim($data[0])."'")->count();
                                if($return_num<1)
                                {
                                    M('boc_order')->where("coupon_sn='".base64_encode(trim($data[4]))."'")->save(array('status'=>1,'out_order_sn'=>trim($data[0]),'lastupdate_time'=>rtrim(date('Y-m-d', strtotime(trim($data[5])))." ".chunk_split(trim($data[6]),2,":"),":")));
//                                    $couponsinfo=M('coupons c')->join('left join vrc_goods g on c.goods_id=g.id')->join('left join vrc_merchant m on g.merchant_id=m.id')->where("c.coupon = '" .base64_encode(trim($data[4])). "'")->field("g.id as goods_id,g.goods_code,stock_num,g.name,g.price,m.merchant_no,merchant_name,m.nike_name,g.pic_url")->find();
//                                    $outputline.= sprintf('%-11s', trim($data[1]))." |#| ".sprintf('%-10s', trim($data[2]))." |#| "."E"." |#| ".sprintf('%-36s', trim($data[4]))." |#| ".sprintf('%-8s', trim($data[5])) ." |#| ".sprintf('%-15s', $couponsinfo['merchant_no']) ." |#| "." |#| "." |#| "." |#| "."\n";
//                                    $use_num++;
//                                    $orderaddition=M('boc_order o')->join("join vrc_goods AS g  ON g.sequence = o.sequence")->field('o.inner_order_sn,o.create_time,g.sequence,g.price')->where("o.coupon_sn='".base64_encode(trim($data[4]))."'")->find();

//                                    $orderList[]=array(
//                                        'inner_order_sn'=>$orderaddition['inner_order_sn'],
//                                        'create_time'=>date('Y-m-d', strtotime(trim($data[5])))." ".chunk_split(trim($data[6]),2,":"),
//                                        'coupon_sn'=>base64_encode(trim($data[4])),
//                                        'sequence'=>$orderaddition['sequence'],
//                                        'out_order_sn'=>trim($data[0]),
//                                        'status'=>1
//                                    );
//
//                                    $details[]=array(
//                                    "orderId"=>trim($data[0]),
//                                    "channelCode"=>"C20093",
//                                    "orderType"=>2,
//                                    "price"=>$orderaddition['price']*100,
//                                     "num"=>1,
//                                    "totalPrice"=>$orderaddition['price']*100,
//                                     'skuCode'=>$orderaddition['sequence'],
//                                     'warehouseCode'=> "A02",
//                                      "orderNo"=>base64_encode(trim($data[4])),
//                                      "taxRate"=>0
//
//                                   );
//
//                                    $post_data[]=array(
//
//                                        'id'=> trim($data[0]),
//                                        "channelCode"=>"C20093",
//                                        "orderNo"=>base64_encode(trim($data[4])),
//                                        "discount"=>0,
//                                        "productTotal"=>2000,
//                                        "orderTotal"=>2000,
//                                        'details'=>$details
//
//
//                                    );
//
//                                    $url="http://192.168.1.9:8000/BOCVirtualVolume/order";
//
//                                    $res=$this->getHttpContent($url, json_encode($post_data));
//
//                                    if($res["httpCode"]=="200")
//                                    {
//
//                                    }else{
//
//                                    }

                                }
                                //

                            }
//                            $outputline.='TLRL'. sprintf('%015s', $use_num);
//
//                            //$outputline.=$arr_end;
//                            $upFileName="CUSED.SHHY.".date("Ymd",strtotime("+1 day")).".00.U";
//                            file_put_contents($bocconfig['upload'].$upFileName,mb_convert_encoding($outputline,'GBK',mb_detect_encoding($outputline,array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'))));
//                            if (PATH_SEPARATOR==':') {
//                                exec(" cd {$bocconfig['upload']} && zip -j {$upFileName}.ZIP  {$upFileName}", $output);
//                            } else {
//                                $this->zip($bocconfig['upload'] . $upFileName. '.ZIP', $bocconfig['upload'] . $upFileName);
//                            }
//                            if(file_exists($bocconfig['upload'] .  $upFileName . '.ZIP')) {
//                                if (PATH_SEPARATOR==':') {
//                                    //$task="java -version";
//                                    $task="cd ". DOC_ROOT . "/Uploads/Boccoupon/Library/" . ";java -jar PgpEncryUtil.jar ". $bocconfig['upload'] .  $upFileName . '.ZIP ' . $bocconfig['upload'] .  $upFileName . '.ZIP.DAT ' . DOC_ROOT . "/Uploads/Boccoupon/Cert/dsfpublic.asc";
//                                    file_put_contents("/logs/excu.log", $task);
//                                } else {
//                                    $task = substr(DOC_ROOT, 0, 2) . "&cd " . DOC_ROOT . "/Uploads/Boccoupon/Library/" . "&java -jar PgpEncryUtil.jar " . $bocconfig['upload'] .  $upFileName . '.ZIP ' . $bocconfig['upload'] .  $upFileName . '.ZIP.DAT ' . DOC_ROOT . "/Uploads/Boccoupon/Cert/dsfpublic.asc";
//
//                                }
//                            }
//                            exec( $task." 2>&1",$out);//
//                            $out[0]='success';
//                            if($out[0]!='success')
//                            {
//                                //$this->error('加密图片文件失败！',U('/Admin/bocvirtualcard/export'));
//                                //exit;
//                                echo "加密优惠券使用文件失败";
//                            }else{
//                                Vendor('FTP.Ftp');
//                                $ftp = new \Ftp(C('BOC_FTP'));
//                                //$conn = $ftp->sftp_connect();
//                                $conn=true;
//                                //echo 8788;
//                                if ($conn) {
//                                    try {
//                                        //$ftp->uploade( $bocconfig['upload'] . $upFileName . '.ZIP.DAT', "/boccopon/upload/" . $upFileName . '.ZIP.DAT');
//                                    } catch (Exception $e){
//                                        $err[]=$e->getMessage();
//                                    }
//                                    if(count($err)>0)
//                                    {
//                                        echo "上传优惠券使用文件失败";
//                                    }else{
////                                         foreach()
////                                         $post_data=array(
////                                         "sap_doc_num"=>'out_order_sn'
////                                        );
//                                        //echo 9976;
////                                        try{
////                                            echo "<pre>";
////                                            print_r($orderList);
////                                            exit;
////                                            $orderReport = D('Admin/OrderReport');
////                                            $filename = "boc_coupon_used_" . date('Ymd') ;
////                                            $orderReport->arrayToExcel($filename,$orderList,1);
////                                            $tomail = 'eddy.chen@g-town.com.cn';
////                                            $excel_path=DOC_ROOT . "/Uploads/Boccoupon/Excel/";
////                                            if(file_exists($excel_path."boc_coupon_" . date('Ymd') . '.xls'))
////                                            {
////                                                $res = sendEamil($tomail, "中国银行虚拟卡券已使用订单", '中国银行虚拟卡券已使用订单,请查收,如有疑问,请及时联系！',$excel_path."boc_coupon_used_" . date('Ymd') . '.xls');
////                                                if (! $res['code']) {
////                                                    //Log::useFiles(storage_path().'/logs/boc365.log');
////                                                    //Log::info('-邮件发送成功');
////                                                    echo "邮件发送成功";
////                                                }
////                                            }
////                                        }catch(Exception $e)
////                                        {
////                                            echo $e->getMessage();
////                                        }
//                                        echo 'success';
//                                    }
//
//                                }else{
//                                    echo "ftp上传优惠券使用文件失败";
//                                }
//                            }


                        }

                    }else{
                        echo "解压失败";
                    }


                }else{
                    echo "文件解密失败";
                }


        }else{
            echo "下载失败";
        }
    }

    private function unzip($path,$zipName){

        $resource = zip_open($path.$zipName);
        while ($dir_resource = zip_read($resource)) {
            $file_content = zip_entry_read($dir_resource,10000000);
            $name =  zip_entry_name($dir_resource);
            $res+= file_put_contents($path.$name,$file_content);
        }
        zip_close($resource);
        return $res;


    }
    private function uzip($path,$file)
    {
        $zip = new  ZipArchive();
        if($zip->open($file)===TRUE) {
            $zip->extractTo($path);//假设解压缩到在当前路径下images文件夹内
            $zip->close();//关闭处理的zip文件
        }
    }

    /**
     *
     * @param string $filename  压缩包位置名称
     * @param unknown $files   需要添加到压缩包文件
     */
    private  function zip($filename ='',$files){

        $zip = new  ZipArchive();
        if ($zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE) {
            return false;
        }

        if(file_exists($files)){
            $zip->addFile( $files, basename($files));//第二个参数是放在压缩包中的文件名称，如果文件可能会有重复，就需要注意一下
        }

        $res = $zip->numFiles;
        $zip->close();

        return $res;
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

    private function getHttpContent($url,$postData)
    {
        $data = '';
        if (!empty($url)) {
            try {
                //$headers = array("Content-type: application/json;charset='utf-8'", "Accept: application/json", "Cache-Control: no-cache", "Pragma: no-cache");
                //$headers = array("Accept: application/json");
                $headers = array("Content-type:application/json");
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt ( $ch, CURLOPT_POST, 1 );
                curl_setopt($ch, CURLOPT_TIMEOUT, 60); //30秒超时
//                curl_setopt ( $ch, CURLOPT_HEADER, 0 );
//                curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
                //print_r($postData);
                curl_setopt ( $ch, CURLOPT_POSTFIELDS,$postData);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                $data = curl_exec($ch);
                    curl_close($ch);
                } catch (Exception $e) {
                    $data = null;
                }
            }
        return json_decode($data,true);
    }


    /*
签名数据：
data：utf-8编码的订单原文，
privatekeyFile：私钥路径
passphrase：私钥密码
返回：base64转码的签名数据
*/
    function sign($data, $privatekeyFile,$passphrase)
    {
        $signature = '';
        $privatekey = openssl_pkey_get_private(file_get_contents($privatekeyFile), $passphrase);
        $res=openssl_get_privatekey($privatekey);
        openssl_sign($data, $signature, $res);
        openssl_free_key($res);

        return base64_encode($signature);
    }

    /*
    验证签名：
    data：原文
    signature：签名
    publicKeyPath：公钥路径
    返回：签名结果，true为验签成功，false为验签失败
    */
    function verity($data, $signature, $publicKeyPath)
    {
        $pubKey = file_get_contents('D:/certs/test.pem');
        $res = openssl_get_publickey($pubKey);
        $result = (bool)openssl_verify($data, base64_decode($signature), $res);
        openssl_free_key($res);

        return $result;
    }


 }
