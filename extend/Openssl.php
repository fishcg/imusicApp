<?php
/** 
* Openssl 加解密
* create by fish
* 2017-08-09
*/  
class Openssl
{
    // 加密
    public static function encrypt($id, string $key){
        $id=serialize($id);
        $data['iv']=base64_encode(substr(randomKeys(18, 1), 0, 16));
        $data['value']=openssl_encrypt($id, 'AES-256-CBC', $key, 0, base64_decode($data['iv']));
        $encrypt=base64_encode(json_encode($data));
        return $encrypt;
    }

    // 解密
    public static function decrypt($encrypt,  string $key)
    {
        $encrypt = json_decode(base64_decode($encrypt), true);
        $iv = base64_decode($encrypt['iv']);
        $decrypt = openssl_decrypt($encrypt['value'], 'AES-256-CBC', $key, 0, $iv);
        $id = unserialize($decrypt);
        if($id){
            return $id;
        }else{
            return 0;
        }
    }
}
