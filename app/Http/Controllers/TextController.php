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
        $echostr = request()->get("echostr", "");
        if ($this->checkSignature() && !empty($echostr)) {
            //第一次接入
            echo $echostr;
        }else{
            $str=file_get_contents("php://input");
            $obj = simplexml_load_string($str,"SimpleXMLElement",LIBXML_NOCDATA);
                switch ($obj->MsgType) {
                    case 'event':
                        if($obj->Event=="subscribe"){
                            //用户扫码的 openID
                            $openid=$obj->FromUserName;//获取发送方的 openid 
                            $access_token=$this->get_access_token();//获取token,
                            $url="https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$access_token."&openid=".$openid."&lang=zh_CN";
                            //掉接口
                            $user=json_decode($this->http_get($url),true);//跳方法 用get  方式调第三方类库
                   // $this->writeLog($fens);
                            if(isset($user["errcode"])){
                                $this->writeLog("获取用户信息失败");
                            }else{
                                //说明查找成功 //可以加入数据库 

                                $content="您好!感谢您的关注";
                            }
                        }
//                        if($obj->Event=="unsubscribe"){
//                                $content="取消关注成功,期待您下次关注";
//
//                        }
                        break;
                }
                 echo $this->xiaoxi($obj,$content);            
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
            echo "access_token: ".$token;
    }
            function xiaoxi($obj,$content){ //返回消息
        //我们可以恢复一个文本|图片|视图|音乐|图文列如文本
            //接收方账号
        $toUserName=$obj->FromUserName;
           //开发者微信号
        $fromUserName=$obj->ToUserName;
           //时间戳
        $time=time();
           //返回类型
        $msgType="text";

        $xml = "<xml>
                      <ToUserName><![CDATA[%s]]></ToUserName>
                      <FromUserName><![CDATA[%s]]></FromUserName>
                      <CreateTime>%s</CreateTime>
                      <MsgType><![CDATA[%s]]></MsgType>
                      <Content><![CDATA[%s]]></Content>
                    </xml>";
            //替换掉上面的参数用 sprintf
        echo sprintf($xml,$toUserName,$fromUserName,$time,$msgType,$content);
    }
}