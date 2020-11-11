<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Redis;
use App\Http\Services\Curl;
use App\Model\UserModel;
use GuzzleHttp\Client;
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
                    $content = '欢迎关注';
                    //根据OPENID获取用户信息（并且入库）
                        //1.获取openid
                    $token=$this->token();
                    $url="https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$token."&openid=".$toUser."&lang=zh_CN";
                    file_put_contents('wx_event.log',$url);
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

                    //%s代表字符串(发送信息)
                    $template = "<xml>
                            <ToUserName><![CDATA[%s]]></ToUserName>
                            <FromUserName><![CDATA[%s]]></FromUserName>
                            <CreateTime>%s</CreateTime>
                            <MsgType><![CDATA[%s]]></MsgType>
                            <Content><![CDATA[%s]]></Content>
                            </xml>";
                    $info = sprintf($template, $toUser, $fromUser, time(), $msgType, $content);
                    return $info;
                }
                //取关
                if (strtolower($data->Event == 'unsubscribe')) {
                    //清除用户的信息
                }
                if(strtolower($data->Event == 'text')){
                    $toUser = $data->FromUserName;
                    $fromUser = $data->ToUserName;
                    $time = time();
                    $msgType = 'text';
                    $content = $content;
                    $xml = "<xml>
                                <ToUserName><![CDATA[".$toUser."]]></ToUserName>
                                <FromUserName><![CDATA[".$fromUser."]]></FromUserName>
                                <CreateTime>".$time."</CreateTime>
                                <MsgType><![CDATA[".$msgType."]]></MsgType>
                                <Content><![CDATA[".$content."]]></Content>
                            </xml>";
                    $info1 = sprintf($xml, $toUser, $fromUser, time(), $msgType, $content);
                    dd($info1)exit;
                    return $info;die;
                }
            }	
            // return true;

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
            // return true;
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
            // echo "有缓存";echo '</br>';

        }else{

            $appid="wx5e164afbbe916954";
            $secret="8ef620ee05e3f29c7e4168a0a607d480";
            $url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$secret;
            // dd($url);exit;
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
    public function guzzle1(){
        $appid="wx5e164afbbe916954";
        $secret="8ef620ee05e3f29c7e4168a0a607d480";
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$secret;

        //使用guzzle发起get请求
        $client = new Client();         //实例化 客户端
        $response = $client->request('GET',$url,['verify'=>false]);       //发起请求并接收响应

        $json_str = $response->getBody();       //服务器的响应数据
        echo $json_str;
    }
    //     public function guzzle2()
    // {
    //     $access_token = $this->token();
    //     $type = 'image';
    //     $url = 'https://api.weixin.qq.com/cgi-bin/media/upload?access_token='.$access_token.'&type='.$type;
    //     // dd($url);exit;
    //     //使用guzzle发起get请求
    //     $client = new Client();         //实例化 客户端
    //     $response = $client->request('POST',$url,[
    //         'verify'    => false,
    //         'multipart' => [
    //             [
    //                 'name'  => 'media',
    //                 'contents'  => fopen('goods.jpg','r')
    //             ],         //上传的文件路径]


    //         ]
    //     ]);       //发起请求并接收响应

    //     $data = $response->getBody();
    //     echo $data;

    // }
    public function menu(){
        $token = $this->token();
        // dd($token);exit;
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$token;
        // dd($url);exit;
        $menu = [
     "button":[
     {  
          "type":"click",
          "name":"今日歌曲",
          "key":"V1001_TODAY_MUSIC"
      },
      {
           "name":"菜单",
           "sub_button":[
           {    
               "type":"view",
               "name":"百度",
               "url":"http://www.baidu.com/"
            },
            {
               "type":"click",
               "name":"赞一下我们",
               "key":"V1001_GOOD"
            }]
            }]
        ];
        // dd($menu);exit;
        $client = new Client();         //实例化 客户端
        $response = $client->request('POST',$url,[
            'verify'    => false,
            'body'  => json_encode($menu)
        ]);

        $json_data = $response->getBody();

        //判断接口返回
        $info = json_decode($json_data,true);
        // dd($info);exit;
        if($info['errcode'] > 0)        //判断错误码
        {
            // TODO 处理错误
        }else{
            // TODO 创建菜单成功逻辑
        }
    }

}