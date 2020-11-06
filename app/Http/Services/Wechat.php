<?php
namespace App\Http\Services;
use App\Accesstoken;
use App\Http\Model\Jsapiticket;

class Wechat{
    public static function get_access_token(){
        $access=Accesstoken::orderBy('access_token_time','desc')->first();

        if($access && (time() - $access->access_token_time)<7000){
            return $access->access_token;
        }else{
            //获取access_token
            $appid="wx081332fd20aa0b5e";
            $secret="ea8f602332da945cb62c6804ccf5e419";
            $url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$secret;
            $data=Curl::http_get($url);
            $data=json_decode($data,true);
            if(isset($data["access_token"])){
                //存入数据库
                $accessModel=new Accesstoken();
                $accessModel->access_token=$data["access_token"];
                $accessModel->access_token_time=time();
                $accessModel->save();
                return $data["access_token"];
            }else{
                return false;
                //file_put_contents("data.txt","请求接口失败",8);
            }

        }
    }

    public static function get_jsapi_ticket(){
    $jsapi = Jsapiticket::orderBy('jsapi_ticket_time','desc')->first();
    if(!$jsapi || time()-$jsapi->jsapi_ticket_time>7000){
        $access_token=self::get_access_token();
        $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=".$access_token."&type=jsapi";
        $result = json_decode(Curl::http_get($url),true);
        if($result['ticket']){
            $jsapiTicket = new Jsapiticket();
            $jsapiTicket->jsapi_ticket =$result['ticket'];
            $jsapiTicket->jsapi_ticket_time =time();
            $jsapiTicket->save();
            return $result['ticket'];
        }else{
            return false;
        }
    }else{
        return $jsapi->jsapi_ticket;
    }

}
}


?>