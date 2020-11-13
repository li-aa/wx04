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

        $xml_str = file_get_contents('php://input');
        $data = simplexml_load_string($xml_str,"SimpleXMLElement",LIBXML_NOCDATA);
          // file_put_contents("shuju.txt",$xml_str,FILE_APPEND);
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
                    if($data->Event == 'CLICK'){
                    if($data->EventKey == 'qian'){
                        $key = 'qian_'.date('Y_m_d',time());// 例子 USER_SIGN_2020_11_11
//                        echo $key;die;
                        $content = '签到成功';
                        $user_sign_info = Redis::zrange($key,0,-1);
                        if(in_array((string)$toUser,$user_sign_info)){
                            $content = '已签到,不可重复签到';
                        }else{
                            Redis::zadd($key,time(),(string)$toUser);
                        }
                        $result = $this->text($toUser,$fromUser,$content);
                        return $result;
                    }
                }
            }
            if(strtolower($data->MsgType)=='text')
            {
//                file_put_contents('laravel-access.log',$postObj);
                switch ($postObj->Content) {
                    case '时间':
                        $content  = date('Y-m-d H:i:s',time());
                        $result = $this->text($toUser,$fromUser,$content);
                        return $result;
                        break;
                    case '图文':
                        $title = '图文测试';
                        $description = '暂无图文描述';
                        $content  = "Eexi1YJmQ9NYVn95CoIB1nHHNnjDs1mjBcs2xK7kPkrAS29rTL8d224U1lqzl1TQ";
                        $url = 'https://www.baidu.com';
                        $result = $this->image_text($toUser,$fromUser,$title,$description,$content,$url);
                        return $result;
                        break;
                    case '天气':
                        $key = '4fdc691ce1494fa6a3ab6d12721ad263';
                        $uri = "https://devapi.qweather.com/v7/weather/now?location=101010100&key=".$key."&gzip=n";
                        $api = file_get_contents($uri);
                        $api = json_decode($api,true);
                        $content = "天气状态：".$api['now']['text'].'
风向：'.$api['now']['windDir'];
                        $result = $this->text($toUser,$fromUser,$content);
                        return $result;
                        break;
                    default:
                        $app_url = 'http://wx.2004.com';
                        $callback = file_get_contents($app_url.'/wx/turing?info='.$postObj->Content);
                        $content = $callback;
                        $result = $this->text($toUser,$fromUser,$content);
                        return $result;
                        break;
                }

            }
        if( $tmpStr == $signature ){
            echo $echostr;die;
        }else{
            return false;
        }
            }   
            // return true;
        

    private function text($toUser,$fromUser,$content)
    {
        $template = "<xml>
                            <ToUserName><![CDATA[%s]]></ToUserName>
                            <FromUserName><![CDATA[%s]]></FromUserName>
                            <CreateTime>%s</CreateTime>
                            <MsgType><![CDATA[%s]]></MsgType>
                            <Content><![CDATA[%s]]></Content>
                            </xml>";
        $info = sprintf($template, $toUser, $fromUser, time(), 'text', $content);
        return $info;
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
        dd($res);exit;
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
        dd($data);
    }
    public function guzzle1(){
        $appid="wx5e164afbbe916954";
        $secret="8ef620ee05e3f29c7e4168a0a607d480";
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$secret;
        // dd($url);exit;
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
                        ],
                        [
                        "name"=> "ee",
                         "sub_button"=> [
                             [
                              "type"=> "view",
                              "name"=> "百度",
                              "key"=> "http://www.baidu.com."
                             ],

                        [
                            'type'  => 'view',
                            'name'  => '天气',
                            'url'   => 'http://www.weather.com.cn'
                        ],
                            [
                            'type'  => 'click',
                            'name'  => '签到',
                            'key'   => 'qian'
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
        dd($res);
    }
        private function image_text($toUser,$fromUser,$title,$description,$content,$url){
        $template = "<xml>
                              <ToUserName><![CDATA[%s]]></ToUserName>
                              <FromUserName><![CDATA[%s]]></FromUserName>
                              <CreateTime>%s</CreateTime>
                              <MsgType><![CDATA[%s]]></MsgType>
                              <ArticleCount><![CDATA[%s]]></ArticleCount>
                              <Articles>
                                <item>
                                  <Title><![CDATA[%s]]></Title>
                                  <Description><![CDATA[%s]]></Description>
                                  <PicUrl><![CDATA[%s]]></PicUrl>
                                  <Url><![CDATA[%s]]></Url>
                                </item>
                              </Articles>
                            </xml>";
        $info = sprintf($template, $toUser, $fromUser, time(), 'news', 1 ,$title,$description,$content,$url);
        return $info;
    }
        public function getWxUserInfo()
    {
        $xml_str = file_get_contents('php://input');
        $data = simplexml_load_string($xml_str);
        $token = $this->token();
        $openid = $data->FromUserName;
        $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$token.'&openid='.$openid.'&lang=zh_CN';
        // dd($url);exit;
        //请求接口
        $client = new Client();
        $response = $client->request('GET',$url,[
            'verify'    => false
        ]);
        return  json_decode($response->getBody(),true);
    }
}