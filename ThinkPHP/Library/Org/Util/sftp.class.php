<?php
/********************************************
 * MODULE:SFTP类
 *******************************************/
namespace Org\Util;
class sftp{
    // 初始配置为NULL
    private $config =NULL ;
    // 连接为NULL
    private $conn = NULL;
    // 是否使用秘钥登陆
    private $use_pubkey_file= true;
    // 初始化
    public function init($config){
        $this->config = $config ;
    }
    // 连接ssh ,连接有两种方式(1) 使用密码
    // (2) 使用秘钥
    public function connect(){
        //echo 6006;exit;
        $methods['hostkey'] = $this->use_pubkey_file ? 'ssh-rsa' : [] ;
        $this->conn = ssh2_connect($this->config['host'], $this->config['port'], $methods);
        //(1) 使用秘钥的时候
        if($this->use_pubkey_file){
            // 用户认证协议
            print_r($this->config);
            $rc = ssh2_auth_pubkey_file($this->conn,$this->config['user'],$this->config['pubkey_file'],$this->config['privkey_file'],$this->config['passphrase']);
            //(2) 使用登陆用户名字和登陆密码
        }else{
            $rc = ssh2_auth_password( $this->conn, $this->config['user'],$this->config['passwd']);
        }
        return $rc ;
    }
    // 传输数据 传输层协议,获得数据
    public function download($remote, $local){
        return ssh2_scp_recv($this->conn, $remote, $local);
    }
    //传输数据 传输层协议,写入ftp服务器数据
//    public function upload($remote, $local,$file_mode=0664){
//        return ssh2_scp_send($this->conn, $local, $remote, $file_mode);
//    }

    function upload($local_file,$remote_file){
        //var_dump($this->connection);exit;
        //echo $local_file;
        //echo $remote_file;
        //echo $remote_file;exit;
        try {

            $res = ssh2_scp_send($this->conn, $local_file, $remote_file, 0644);
        }catch(Exception $e)
        {
            echo $e->getMessage();
        }

        return $res;
    }

    // 删除文件
    public function remove($remote){
        $sftp = ssh2_sftp($this->conn);
        $rc = false;
        if (is_dir("ssh2.sftp://{$sftp}/{$remote}")) {
            $rc = false ;
            // ssh 删除文件夹
            $rc = ssh2_sftp_rmdir($sftp, $remote);
        } else {
            // 删除文件
            $rc = ssh2_sftp_unlink($sftp, $remote);
        }
        return $rc;
    }





}
?>