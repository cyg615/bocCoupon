<?php

/**
 * Created by PhpStorm.
 * User: cyg
 * Date: 2017/9/18
 * Time: 15:22
 */
class Ftp
{
    private $ftpObj;

    private $ftpHost;
    // 服务器地址
    private $ftpPort;
    // 服务器端口
    private $ftpUser;
    // 用户名
    private $ftpPassword;
    // 口令
    private $localBase;

    private  $connection;
    // 你存放的目录
    function __construct($initData = array())
    {
        if (isset($initData['ftpHost']) && $initData['ftpHost']) {
            $this->ftpHost = $initData['ftpHost'];
        }
        if (isset($initData['ftpPort']) && $initData['ftpPort']) {
            $this->ftpPort = $initData['ftpPort'];
        }
        if (isset($initData['ftpUser']) && $initData['ftpUser']) {
            $this->ftpUser = $initData['ftpUser'];
        }
        if (isset($initData['ftpPassword']) && $initData['ftpPassword']) {
            $this->ftpPassword = $initData['ftpPassword'];
        }
        if (isset($initData['localBase']) && $initData['localBase']) {
            $this->localBase = $initData['localBase'];
        }
    }

    function sftp_connect()
    {
        if (! $this->ftpObj) {
            $this->connection = ssh2_connect($this->ftpHost, $this->ftpPort);
            if ($this->connection) {
                if (ssh2_auth_password($this->connection, $this->ftpUser, $this->ftpPassword)) {
                    $this->ftpObj = @ssh2_sftp($this->connection);
                    //var_dump($this->ftpObj);exit;
                    return true;
                } else
                    return false;
            } else
                return false;
        }

        return true;
    }
    /**
     * 拉去ftp文件下载到指定目录
     * @param unknown $filename
     * @param unknown $outPath
     * @return number
     */
    function download($filename,$outPath){
        $connection = ssh2_connect($this->ftpHost, $this->ftpPort);
        $res = ssh2_auth_password($connection, $this->ftpUser, $this->ftpPassword);
        return   ssh2_scp_recv($connection, "/boccopon/download/{$filename}", $outPath);
      
      /*   $stream = @fopen("ssh2.sftp://$this->ftpObj/boccopon/download/{$filename}", 'r');
        //echo "ssh2.sftp://$this->ftpObj/boccopon/download/{$filename}";exit;
        //var_dump( $stream );
        if($stream){
            return file_put_contents($outPath, $stream);
        }
        return -1; */

    }

    function uploade($local_file,$remote_file){
        $res = ssh2_scp_send( $this->connection, $local_file, $remote_file,0644);
        return $res;
    }
}