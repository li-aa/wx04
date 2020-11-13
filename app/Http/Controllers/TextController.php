<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Redis;
use App\Http\Services\Curl;
use App\Model\UserModel;
use App\Model\MediaModel;
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

            }	
            // return true;
        }
}

    private function check()
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
        public function wxEvent()
    {
               //验证通过
            // 1 接收数据
            $xml_str = file_get_contents("php://input");
            $log_str = date('Y-m-d H:i:s') . ' >>>>>  ' . $xml_str ." \n\n";
            file_put_contents('wx_event.log',$log_str,FILE_APPEND);
            // 将接收来的数据转化为对象
            $obj = simplexml_load_string($xml_str);//将文件转换成 对象
            $this->xml_obj = $obj;
            $msg_type = $obj->MsgType;  
             switch($msg_type)
        {
            case 'event' :
                break;
            case 'text' :           //处理文本信息
                $this->textHandler();
                break;
            case 'image' :          // 处理图片信息
                $this->imageHandler($obj);
                break;
            case 'voice' :          // 语音
                $this->voiceHandler();
                break;
            case 'video' :          // 视频
                $this->videoHandler();
                break;
            default:
                echo 'default';
            }
            echo "";
        }
    
        //图片

        protected function imageHandler($obj){

        //入库

        //下载素材
        $token = $this->token();
        $media_id = $obj->MediaId;
        //dd($media_id);exit;
        $url = 'https://api.weixin.qq.com/cgi-bin/media/get?access_token='.$token.'&media_id='.$media_id;
        $img = file_get_contents($url);
        // dd($img);exit;
        // $media_path = 'upload/good.jpg';
        $res = file_put_contents("kkk.jpg",$img);
        // dd($res);exit;
        // return $res;
        if($res)
        {
            echo "保存成功";
        }else{
            // TODO 保存失败
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
        $appid="wxa82a04c5cb10790b";
        $secret="bc9aecbb5c20e9a4c85d43edc71bed13";
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
        dd($token);exit;
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$token;
        // dd($url);exit;
        $menu = [
                    'button'    => [
                        [
                            'type'  => 'click',
                            'name'  => 'WX2004',
                            'key'   => 'k_wx_2004'
                        ],
                        [
                             'type'  => 'view',
                            'name'  => '商城',
                            'url'   => 'http://pcl.mazhanliang.top'
                        ],[
                        "name"=> "ee",
                         "sub_button"=> [
                             [
                              "type"=> "pic_sysphoto",
                              "name"=> "xitong",
                              "key"=> "rselfmenu_1_0"
                             ],

                        [
                            'type'  => 'view',
                            'name'  => '天气',
                            'url'   => 'http://wx.2004.com/wx/tianqi'
                        ],
                            [
                            'type'  => 'click',
                            'name'  => '签到',
                            'key'   => 'qian'
                        ],
                             [
                              "type"=> "pic_photo_or_album",
                              "name"=> "paizhao",
                              "key"=> "rselfmenu_1_1"
                             ],
                             [
                               "type"=> "pic_weixin",
                               "name"=> "weixin",
                               "key"=> "rselfmenu_1_2"
                             ]
                          ]
                        ]
                    ],
                ];

        // dd($menu);exit;
        $client = new Client();         //实例化 客户端
        $response = $client->request('POST',$url,[
            'verify'    => false,
            'body'  => json_encode($menu,JSON_UNESCAPED_UNICODE)
        ]);

        $json_data = $response->getBody();
        echo $json_data;exit;
        //判断接口返回
        $info = json_decode($json_data,true,);
        // dd($info);exit;
        if($info['errcode'] > 0)        //判断错误码
        {
            // TODO 处理错误
        }else{
            // TODO 创建菜单成功逻辑
        }
    }
    public function media(){
        $token = $this->token();
        $media_id = '1YxPJimb14FB0GVtO7vpX5ye82LZwkMCT5UBONQhMJSMac1EBKD0X5L8fP10KzPX';
        $url = 'https://api.weixin.qq.com/cgi-bin/media/get?access_token='.$token.'&media_id='.$media_id;
        $img = file_get_contents($url);
        $res = file_put_contents('good.jpg',$img);
        var_dump($res);
    }
}