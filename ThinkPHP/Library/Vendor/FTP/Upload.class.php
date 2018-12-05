<?php

/**
 * Created by PhpStorm.
 * User: cyg
 * Date: 2017/9/18
 * Time: 15:25
 */
class Upload
{

}

header("Content-type: text/html; charset=utf-8");
date_default_timezone_set("PRC");
error_reporting(E_ALL || ~ E_NOTICE);
require 'fun.php';
if (php_uname('s') != 'Windows NT')
    $path_h = "/boccopon/";
else
    $path_h = "c:/";
$decryptPath = $path_h . 'accessOrder/txt/' . date('Ymd') . '/';
;
$encryptkey = '0135792468abcdefghijklmn';
$rece_name = 'RECE_000022_' . date('Ymd') . '_';
$zipPath = $path_h . "accessOrder/rece/zip/" . date('Ymd') . '/';
$refundzipPath = $path_h . "accessOrder/refund/zip/";
mkdirFile($zipPath);
$gtownTxtPath = $path_h . "accessOrder/rece/txt/" . date('Ymd') . '/';
mkdirFile($gtownTxtPath);
$gtownrefundPath = $path_h . "accessOrder/refund/";
$logPath = $path_h . 'accessOrder/access.log';
$config = array(
    'ftpHost' => "175.102.18.35",
    'ftpPort' => 22,
    'ftpUser' => "root",
    'ftpPassword' => '12Gtown34'
);
$step = ceil(date('H')) >= 11 ? 2 : 1;
$filename = 'RECE_000022_' . date('Ymd') . '_01_' . $step; // 反馈的文件名称

$refundList = array();
// 推送时间段 8-20点内
$time = ceil(date('H'));
if ($time <= 8 || $time >= 20) {
    exit();
}

// 脚本start
// 获取需未完成的订单
$orderlist = getPushOrder();
$pushOrderList = array();
$sqlserverConn = getsqlserverconn();
foreach ($orderlist as $key => $pushorder) {
    $tempSapOrder = getSapOrder($sqlserverConn, $pushorder['order_code']);

    /*
     * if(!$tempSapOrder||!$tempSapOrder['U_ExpNo']){ //无快递单号不推送
     * continue;
     * }
     */
    $status = getPushOrderstatus($pushorder['order_code']);

    if ($status != statustozhongyin($tempSapOrder['U_OStatus'])) { // 状态不相同 需要推送
        $pushOrderList[$pushorder['order_code']] = getSapOrder($sqlserverConn, $pushorder['order_code']);
    }
}
if (! $pushOrderList) {
    // 没有找到状态更新的订单
    echo 'not found push order ';
    file_put_contents($logPath, date("Y-m-d H:i:s") . 'not found push order ' . "\n", FILE_APPEND);
    exit();
}
;
$str = '';
foreach ($pushOrderList as $order) {
    $str .= sapOrderToStr($order);
}

if (! $str) {
    file_put_contents($logPath, date("Y-m-d H:i:s") . '-无内容' . "\n", FILE_APPEND);
}
$tempArr = explode("\n", $str);
$count = str_pad(count($tempArr) - 1, 8, '0', STR_PAD_LEFT);
$pushStr = "[TxnDetailStart]" . "\n" . $str . '[TxnDetailEnd]' . $count;

/**
 * 加密文件 上传FTP等操作
 */

require 'ftp.php';

// 写入备份文件 明文
file_put_contents($gtownTxtPath . $filename . '.TXT', $pushStr);

// $pushStr= iconv("utf-8//IGNORE","gb2312",$pushStr);
require 'des.php';
$dec = new Crypt3Des($encryptkey);
$str = $dec->encrypt($pushStr); // 加密
file_put_contents($zipPath . $filename . '.TXT', $str); // 加密后的TXT

// 生产加密压缩包
if (php_uname('s') != 'Windows NT') {
    exec(" cd {$zipPath} && zip -j {$filename}.ZIP  {$filename}.TXT", $output);
} else {
    require 'zip.php';
    zip($zipPath . $filename . '.ZIP', $zipPath . $filename . '.TXT');
}


// 发送到FTP
$ftp = new FtpClass($config);
$conn = $ftp->sftp_connect();
if ($conn) {
    $res = $ftp->uploade($zipPath . $filename . '.ZIP', "/onlinemart/supplier/000022/rece/" . $filename . '.ZIP');
    if ($res) {
        file_put_contents($logPath, date("Y-m-d H:i:s") . '-发送加密压缩包成功' . "\n", FILE_APPEND);
    }
}

if ($refundList) {
    // 如有退款订单 生成文件 发送邮件通知
    $refundStr = saverefund($refundList);
    $str = $dec->encrypt($refundStr); // 加密
    $n = date('H') > 14 ? 2 : 1;
    $refund_filename = "RECE_000022_" . date('Ymd') . "_02_{$n}.";
    file_put_contents($gtownrefundPath . 'encrypt/' . $refund_filename . 'TXT', $str);
    // 生产加密压缩包
    if (php_uname('s') != 'Windows NT') {
        exec(" cd {$refundzipPath} && zip -j {$refund_filename}ZIP  {$gtownrefundPath}encrypt/{$refund_filename}TXT", $output);
    } else {
        require_once ('zip.php');
        zip($refundzipPath . $refund_filename . 'ZIP', $gtownrefundPath . 'encrypt/' . $refund_filename . 'TXT');
    }
    if ($conn) {
        $res = $ftp->uploade($refundzipPath . $refund_filename . 'ZIP', "/onlinemart/supplier/000022/rece/" . $refund_filename . 'ZIP');
        if ($res) {
            file_put_contents($logPath, date("Y-m-d H:i:s") . '-发送加密退款文件成功' . "\n", FILE_APPEND);
        }
    }
}