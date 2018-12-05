<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Home\Controller;
use Think\Controller;

/**
 * 前台公共控制器
 * 为防止多分组Controller名称冲突，公共Controller名称统一使用分组名称
 */
class HomeController extends Controller {

	/* 空操作，用于输出404页面 */
	public function _empty(){
		$this->redirect('Index/index');
	}


    protected function _initialize(){
        /* 读取站点配置 */
        $config = api('Config/lists');
        C($config); //添加配置

        if(!C('WEB_SITE_CLOSE')){
            $this->error('站点已经关闭，请稍后访问~');
        }
    }

	/* 用户登录检测 */
	protected function login(){
		/* 用户登录检测 */
		is_login() || $this->error('您还没有登录，请先登录！', U('User/login'));
	}
    public function export()
    {
        Vendor('FTP.Ftp');
        $ftp = new \Ftp(C('BOC_FTP'));
        $conn = $ftp->sftp_connect();
        if ($conn) {

            $res = $ftp->uploade("\\var\\www\\html\\vr_couponadmin\\Uploads\\Boccoupon\\Upload\\WARES.SHHY.20170927.00.P.ZIP.DAT ", "\\boccopon\\upload\\" . $goodsFileName . '.ZIP.DAT ');
            //$res = $ftp->uploade( $bocconfig['upload'] . $goodsFileName . '.ZIP.DAT ', "/boccopon/upload/" . $goodsFileName . '.ZIP.DAT ');
            //echo $bocconfig['upload'] . $goodsFileName . '.ZIP.DAT '."<br>";
            var_dump($res);
            if ($res) {
                echo "上传成功";
                //echo "sss";
                //$this->success('上传成功！',U('/Admin/bocvirtualcard/export'));

                //file_put_contents($logPath, date("Y-m-d H:i:s") . '-发送加密压缩包成功' . "\n", FILE_APPEND);
            } else {
                echo "上传失败";
            }
        } else {
            echo "连接失败";
        }
    }
}
