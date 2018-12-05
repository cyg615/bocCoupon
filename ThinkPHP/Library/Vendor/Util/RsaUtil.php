<?php
/**
 * Created by PhpStorm.
 * User: cyg
 * Date: 2018/5/7
 * Time: 15:36
 */
class RsaUtil
{
    public function encrypCode($publicKey, $str)
    {
        //$publicKey = $this->b64encode_urlsafe($publicKey);//转换base64
        $pem = chunk_split($publicKey, 64, "\n");//转换为pem格式的公钥
        $pem = "-----BEGIN PUBLIC KEY-----\n" . $pem . "-----END PUBLIC KEY-----\n";
        $pu_key =$pu_key =  openssl_pkey_get_public($pem);
        //echo  $pem;exit;
        //openssl_pkey_get_public
        //$pu_key = openssl_x509_export($pem);
//        $crypto = '';
//        foreach (str_split($str, 117) as $chunk) {
//            openssl_public_encrypt($chunk, $encryptData, $pu_key);
//            $crypto .= $encryptData;
//        }
        openssl_public_encrypt($str, $encryptData, $pu_key);
        $encrypted = $this->urlsafe_b64encode($encryptData);
        return $encrypted;
    }

    function b64encode_urlsafe($string)
    {
        $data = str_replace(array('-', '_', ''), array('+', '/', '='), $string);
        return $data;
    }

    function urlsafe_b64encode($string)
    {
        $data = base64_encode($string);
        $data = str_replace(array('+', '/', '='), array('-', '_', ''), $data);
        return $data;
    }


}