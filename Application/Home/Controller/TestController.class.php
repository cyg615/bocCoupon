<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Home\Controller;

/**
 * 用户控制器
 * 包括用户中心，用户登录及注册
 */
class TestController extends HomeController {

	/* 用户中心首页 */
	public function index(){
	    //echo 999;exit;
        $config = array(
            'host'     =>'123.124.191.195', //服务器
            'privkey_file'=>DOC_ROOT . "/Uploads/Boccoupon/Cert/id_rsa",
            'pubkey_file'=>DOC_ROOT . "/Uploads/Boccoupon/Cert/id_rsa",
            'port'     => '988', //端口
            'username' =>'SHHY', //用户名
            'password' =>'', //密码
            'passphrase'=>''
        );
        $ftp = new \Org\Util\sftp();
        $ftp->init($config);
        var_dump($ftp->connect());
        exit;
//        $localpath="E:/www/new_20170724.csv";
//        $serverpath='/new_20170724.csv';
        $bocconfig=C('BOC_COUPON_CONFIG');
        //$ftp->uploade( $bocconfig['upload'] . $upFileName . '.ZIP.DAT', "/boccopon/upload/" . $upFileName . '.ZIP.DAT');
        $st = $ftp->upload($bocconfig['upload']."logo.png","/unioncd/SHHY/upload/logo.png");     //上传指定文件
        if($st == true){
            echo "success";

        }else{
            echo "fail";
        }
        //gpg();
	}
	 public function excel()
     {
         Vendor('Crypt3Des.Crypt3Des');
         Vendor('Excel.PHPExcel');
         $Crypt3Des=\Crypt3Des::instance();
         //$inputFileName = C('DOWNLOAD_UPLOAD.rootPath') . $info['uploadfile']["savepath"] . $info['uploadfile']["savename"];
         $inputFileName="D:/0510/0511status .xls";
         $objPHPExcel = \PHPExcel_IOFactory::load($inputFileName);
         $sheetData = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);

         for ($i = 2; $i <= count($sheetData); $i++) {
                   if($sheetData[$i]["B"]!=$sheetData[$i]["C"])
                   {
                       echo $sheetData[$i]["A"]."<br>";

                   }
             }

         }

}
