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
         $echostr = request()->get("echostr", "");
        if ($this->checkSignature() && !empty($echostr)) {

            //第一次接入
            echo $echostr;
        }else{
            $str=file_get_contents("php://input");
            $obj = simplexml_load_string($str,"SimpleXMLElement",LIBXML_NOCDATA);
            $content="鹅鹅鹅，尚未开发....见谅,当前机器人已开发,您可以用输入或发送语音来与机器人进行聊天,输入“天气:地区”(如 天气:邯郸) 或 地区 来进行查询天气 ";

            file_put_contents("shuju.txt",$str,FILE_APPEND);
                if($obj->EventKey=="qian") {
                 $key = $obj->FromUserName;
                 $times = date("Y-m-d", time());
                 $date = Redis::zrange($key, 0, -1);
                if ($date) {
                     $date = $date[0];
                 }
                if ($date == $times) {
                     $content = "您今日已经签到过了!";
                } else {
                         $zcard = Redis::zcard($key);
                    if ($zcard >= 1) {
                        Redis::zremrangebyrank($key, 0, 0);
                    }
                    $keys = json_decode(json_encode($obj),true);


                    $keys = $keys['FromUserName'];
                    $zincrby = Redis::zincrby($key, 1, $keys);
                    $zadd = Redis::zadd($key, $zincrby, $times);
                    $content = "签到成功您以积累签到" . $zincrby . "天!";
                }

            }
            switch ($obj->MsgType) {
                case "event":
                    if ($obj->Event == "subscribe") {
                        //用户扫码的 openID
                        $openid = $obj->FromUserName;//获取发送方的 openid
                        $access_token = $this->get_access_token();//获取token,
                        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=" . $access_token . "&openid=" . $openid . "&lang=zh_CN";
                        //掉接口
                        $user = json_decode($this->http_get($url), true);//跳方法 用get  方式调第三方类库
                        // $this->writeLog($fens);
                        if (isset($user["errcode"])) {
                            $this->writeLog("获取用户信息失败");
                        } else {
                            //说明查找成功 //可以加入数据库
                            $res = UserModel::where("openid", $openid)->first();//查看用户表中是否有该用户,查看用户是否关注过
                            if ($res) {//说明该用户关注过
                                $openid = $obj->FromUserName;
                                $res = UserModel::where("openid", $openid)->first();
                                $res->subscribe = 1;
                                $res->save();
                                $content = "欢迎您再次关注！";
                            } else {
                                $data = [
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
                                UserModel::create($data);
                                $content = "欢迎关注";
                            }
                        }
                    }
                    // 取消关注
                    if ($obj->Event == "unsubscribe") {
                        $openid = $obj->FromUserName;
                        $res = UserModel::where("openid", $openid)->first();
                        $res->subscribe = 0;
                        $res->save();
                        $content="";
                    }
                    break;
        }

    }   
            // return true;
    private function checkSignature()
    {
        $signature = request()->get("signature");
        $timestamp = request()->get("timestamp");
        $nonce = request()->get("nonce");

        $token ="TOKEN";
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
    // public function media(){
    //     $token = $this->token();
    //     $media_id = '1YxPJimb14FB0GVtO7vpX5ye82LZwkMCT5UBONQhMJSMac1EBKD0X5L8fP10KzPX';
    //     $url = 'https://api.weixin.qq.com/cgi-bin/media/get?access_token='.$token.'&media_id='.$media_id;
    //     $img = file_get_contents($url);
    //     $res = file_put_contents('good.jpg',$img);
    //     dd($res);
    // }
    //     private function image_text($toUser,$fromUser,$title,$description,$content,$url){
    //     $template = "<xml>
    //                           <ToUserName><![CDATA[%s]]></ToUserName>
    //                           <FromUserName><![CDATA[%s]]></FromUserName>
    //                           <CreateTime>%s</CreateTime>
    //                           <MsgType><![CDATA[%s]]></MsgType>
    //                           <ArticleCount><![CDATA[%s]]></ArticleCount>
    //                           <Articles>
    //                             <item>
    //                               <Title><![CDATA[%s]]></Title>
    //                               <Description><![CDATA[%s]]></Description>
    //                               <PicUrl><![CDATA[%s]]></PicUrl>
    //                               <Url><![CDATA[%s]]></Url>
    //                             </item>
    //                           </Articles>
    //                         </xml>";
    //     $info = sprintf($template, $toUser, $fromUser, time(), 'news', 1 ,$title,$description,$content,$url);
    //     return $info;
    // }
    //     public function getWxUserInfo()
    // {
    //     $xml_str = file_get_contents('php://input');
    //     $data = simplexml_load_string($xml_str);
    //     $token = $this->token();
    //     $openid = $data->FromUserName;
    //     $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$token.'&openid='.$openid.'&lang=zh_CN';
    //     // dd($url);exit;
    //     //请求接口
    //     $client = new Client();
    //     $response = $client->request('GET',$url,[
    //         'verify'    => false
    //     ]);
    //     return  json_decode($response->getBody(),true);
    // }
}