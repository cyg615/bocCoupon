<?php
//namespace PLibrary;
/**
 * 3des加密  密码模式CBC PKCS5填充模式
 * @author dem
 *
 */
class Crypt3Des {
    
    public  static  $instance;
    private static $key = null;
    private static $iv = null;
    public  static function instance($key,$iv)
    {
        if (self::$instance == NULL) {
            self::$instance = new self();
            self::$key = $key;
            self::$iv = $iv;
        }

        return self::$instance;
    }
    
  /**
   * 加密
   * @param unknown $key
   * @param unknown $iv
   * @param unknown $input
   * @return string
   */
    function encrypt($input)
    {  
        $td = mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_CBC, '');
        mcrypt_generic_init($td, self::$key, self::$iv);
        //$str = base64_encode(mcrypt_generic($td,$this->pkcs5_pad($input,8)));
        $str = mcrypt_generic($td,$this->pkcs5_pad($input,8));
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return  bin2hex($str);
    }
    
    /**
     * 解密
     * @param unknown $key
     * @param unknown $iv
     * @param unknown $encrypted
     * @return Ambigous <boolean, string>
     */
    function decrypt($encrypted)
    {   $encrypted = $this->hex2bin($encrypted);
        $td = mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_CBC, '');
        mcrypt_generic_init($td, self::$key,self::$iv);
        $str  = $this->pkcs5_unpad(mdecrypt_generic($td, $encrypted));
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return $str;
    }
    
    function pkcs5_pad($text, $blocksize)
    {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }
    
    
    function pkcs5_unpad($text)
    {
        $pad = ord($text{strlen($text)-1});
        if ($pad > strlen($text))
        {
            return false;
        }
        if( strspn($text, chr($pad), strlen($text) - $pad) != $pad)
        {
            return false;
        }
        return substr($text, 0, -1 * $pad);
    }
    
    
    function PaddingPKCS7($data)
    {
        $block_size = mcrypt_get_block_size(MCRYPT_3DES, MCRYPT_MODE_ECB);
        $padding_char = $block_size - (strlen($data) % $block_size);
        $data .= str_repeat(chr($padding_char), $padding_char);
        return $data;
    }
    
    protected function hex2bin($hexdata) {
        $bindata = '';
    
        for ($i = 0; $i< strlen($hexdata); $i += 2) {
            $bindata .= chr(hexdec(substr($hexdata, $i, 2)));
        }
    
        return $bindata;
    }
    
    
     
}