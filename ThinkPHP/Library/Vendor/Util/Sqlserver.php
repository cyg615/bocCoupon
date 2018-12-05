<?php
//namespace Util;
//namespace Home\Org\Util;
 class Sqlserver{
//     public $sqlserver  = 'CYG-PC\SQLEXPRESS';
//     public $sqldatabase= 'sap';
//     public $sqluser    = 'sa';
//     public $sqlpassword= '123456';
//     public $connenctObj=null;
     public $sqlserver  = '192.168.1.10';
     public $sqldatabase= 'GT_TEST';
     public $sqluser    = 'molly';
     public $sqlpassword= 'Gtown123';
     public $connenctObj=null;

     
     public function __construct($config=null) {
         if($config){
             $this->sqlserver=$config['host'];
             $this->sqldatabase = $config['db'];
             $this->sqluser = $config['user'];
             $this->sqlpassword = $config['password'];
         }
         if($this->connenctObj==null)
             $this->connenctObj= odbc_connect("Driver={SQL Server};Server=$this->sqlserver;Database=$this->sqldatabase;", $this->sqluser, $this->sqlpassword);
     }
     
     function connent(){
       if($this->connenctObj==null)
       $this->connenctObj= odbc_connect("Driver={SQL Server};Server=$this->sqlserver;Database=$this->sqldatabase;", $this->sqluser, $this->sqlpassword);
          
     }
     
     function query($sqlquery){
         //$sqlquery= iconv("utf-8","gb2312",$sqlquery);
         $result=odbc_exec($this->connenctObj,$sqlquery);
         //var_dump($result);exit;
         while ($data[] = odbc_fetch_array($result));
         array_pop($data);
         $keylist = array_keys($data[0]);
         if(!isset($data[0])){
             return false;
         }
         foreach($data as $key=>$value){
            if(!$value)
                continue;
             foreach($keylist as $keyname){
              $keyencoding =  mb_detect_encoding($value[$keyname],array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'));
              //error_log('字段：'.$keyname.'  值：'.$value[$keyname].' 编码格式:'.$keyencoding);
              if($keyencoding=='UTF-8'){
                 if(md5($data[$key][$keyname])=='a145d59f9e26d6332893788cd47c5aa9')
                     $data[$key][$keyname] = '圆通'; //此处处理 快递名称为utf8编码确显示gbk异常情况
                 else
                 $data[$key][$keyname]=mb_convert_encoding($value[$keyname],'utf-8','utf-8');//$this->charsetToUTF8();
              }else
               $data[$key][$keyname]=mb_convert_encoding($value[$keyname],'utf-8',mb_detect_encoding($value[$keyname],array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5')));//$this->charsetToUTF8();
             }
         }
         return  $data;
     }
     
     function saveBysql($sql){
        // $sql= iconv("utf-8","gb2312",$sql);
         $res = odbc_do($this->connenctObj,$sql);
         $res = odbc_num_rows($res);
         return $res;
         
     }
     
     function close(){
         odbc_close($this->connenct);
     }
     
     function  __destruct(){
         odbc_close($this->connenct);
     }
     
 }