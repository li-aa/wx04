<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Redis;
use App\Http\Services\Curl;
class TextController extends Controller
{
    public function aa(){
    	$echostr = request()->get("echostr", "");
        if ($this->checkSignature() && !empty($echostr)) {

            //第一次接入
            echo $echostr;
        }
    }
    private function checkSignature()
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
        return true;
    }else{
        return false;
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

            $data = json_decode($response,true);
            $token = $data['access_token'];

            //保存到Redis中 时间为 3600
            Redis::set($key,$token);
            Redis::expire($key,3600);
        }
            echo "access_token: ".$token;
    }

}