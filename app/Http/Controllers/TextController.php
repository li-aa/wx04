<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Redis;
use App\Http\Services\Curl;
class TextController extends Controller
{
    public function index()
	{
    $signature = $_GET["signature"];
    $timestamp = $_GET["timestamp"];
    $nonce = $_GET["nonce"];
	
    $token = 'TOKEN';
    $tmpArr = array($token, $timestamp, $nonce);
    sort($tmpArr, SORT_STRING);
    $tmpStr = implode( $tmpArr );
    $tmpStr = sha1( $tmpStr );
    
    if( $tmpStr == $signature ){
        $xml_str = file_get_contents('php://input');
        $data = simplexml_load_string($xml_str, 'SimpleXMLElement', LIBXML_NOCDATA);
         if (strtolower($data->MsgType) == "event") {
                //关注
                if (strtolower($data->Event == 'subscribe')) {
                    //回复用户消息(纯文本格式)
                    $toUser = $data->FromUserName;
                    $fromUser = $data->ToUserName;
                    $msgType = 'text';
                    $content = '欢迎关注了我';
                    $token=$this->token();
                    $url="https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$token."&openid=".$toUser."&lang=zh_CN";
                    file_put_contents('user_access.log',$url);
                    $user=file_get_contents($url);
                    $user=json_decode($user,true);
                    $wxuser=UserModel::where('openid',$user['openid'])->first();
                    if(!empty($wxuser)){
                        $content="欢迎回来";
                    }else{
                        $data=[
                                    "subscribe" => $user['subscribe'],
                                    "openid" => $user["openid"],
                                    "nickname" => $user["nickname"],
                                    "sex" => $user["sex"],
                                    "city" => $user["city"],
                                    "country" => $user["country"],
                                    "province" => $user["province"],
                                    "language" => $user["language"],
                                    "headimgurl" => $user["headimgurl"],
                                    "subscribe_time" => $user["subscribe_time"],
                                    "subscribe_scene" => $user["subscribe_scene"]
                        ];
                        $data=UserModel::insert($data);
                    }
                }
                if (strtolower($data->Event == 'unsubscribe')) {
                   //清除用户的信息
                }
    }else{
        return false;
    }
	}
}
        public function wxEvent()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = 'TOKEN';
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){            //验证通过
            // 1 接收数据
            $xml_str = file_get_contents("php://input");

            // 记录日志
            file_put_contents('wx_event.log',$xml_str);
            echo "";
            die;
        }else{
            echo "";
        }
    }
    public function token(){
          $key = 'wx:access_token';

        //检查是否有 token
        $token = Redis::get($key);
        if($token)
        {
            echo "有缓存";echo '</br>';

        }else{

            $appid="wx5e164afbbe916954";
            $secret="8ef620ee05e3f29c7e4168a0a607d480";
            $url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$secret;
            $response = file_get_contents($url);
            // dd($response);exit;
            $data = json_decode($response,true);
            $token = $data['access_token'];

            //保存到Redis中 时间为 3600
            Redis::set($key,$token);
            Redis::expire($key,3600);
        }
            return $token;
    }

    public function ccc(){
        $xml_str = file_get_contents("php://input");
        // dd($xml_str);exit;
        $data = simplexml_load_string($xml_str);
        print_r($data);
    }
}