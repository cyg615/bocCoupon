<?php
/**
 * 异步上传图片
 * @var Dem
 */
error_reporting(E_ERROR);
$from=$_POST['from'];
if($from=="brand")
{
    $file = 'Uploads/Brand/'.date('Ymd');
}else{
    $file = 'Uploads/Goods/'.date('Ymd');
}

$host = $_SERVER['HTTP_ORIGIN'];
if(!file_exists($file)){
    mkdir($file);
}

$smeta = $_POST['imga']; //获取base64
if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $smeta, $result)) { // base64上传
    $data = base64_decode(str_replace($result[1], '', $smeta));
    $uniqid = uniqid();
    $dataname = $file.'/'.$uniqid . '.' . $result[2];
    //$dataname_small=$file.'/'.$uniqid.'_small' . '.' . $result[2];
	//echo  $dataname;
    if (file_put_contents($dataname, $data)) {
        //file_put_contents($dataname_small, $data);
        
        echo json_encode(array('picUrl'=>$host.'/'.$dataname));
    } else {
        echo 'Error';
    }
}