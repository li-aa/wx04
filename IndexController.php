<?php

namespace App\Http\Controllers\WX;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Model\UserModel;
class IndexController extends Controller
{
    //
    public function  index(){
        $echostr = request()->get("echostr", "");
        if ($this->checkSignature() && !empty($echostr)) {

            //第一次接入
            echo $echostr;
        }else{

           // $access_token=$this->get_access_token();  //跳方法  调 access_token  获取access_token
            $str=file_get_contents("php://input");
            $obj = simplexml_load_string($str,"SimpleXMLElement",LIBXML_NOCDATA);
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
                case "text":
                    //首先呢先要判断一下，接来的用户消息中是否有 包含天气：地址，
                            // 把用户发来的消息中 天气：替换为空，使用 str_replace 函数
                          $dizhi=urlencode(str_replace("天气:","",$obj->Content));
                           // file_put_contents("aa.txt",$dizhi);
                          $url="http://apis.juhe.cn/simpleWeather/query?city=".$dizhi."&key=2f3d1615c28f0a5bc54da5082c4c1c0c";
//                         file_put_contents("tianqishuju.txt",$this->http_get($url));
                            $shuju=json_decode($this->http_get($url),true);
                     if($shuju["error_code"]==0){//接口地址说明 error_code 为零说明调用成功
                                //走到这里说明已经调用成功.
                                $content="";
//                                file_put_contents("zotong.txt","走通");
                                //$content.=$shuju["reason"]."\n"; //查询成功，
                                $content.=$shuju["result"]["city"]."当天天气"."\n";//查询的城市
                                    $dangqian=$shuju["result"]["realtime"];
                                $content.="温度☞".$dangqian["temperature"]."\n";// 当前温度.
                                $content.="湿度☞".$dangqian["humidity"]."\n";//humidity 湿度
                                $content.="天气情况☞".$dangqian["info"]."\n";   //info 天气情况
                                $content.="风向☞".$dangqian["direct"]."\n"; //direct 风向
                                $content.="风力☞".$dangqian["power"]."\n"; //direct 风力
                                $content.="空气质量☞".$dangqian["aqi"]."\n"; //direct 空气质量
                                $content.="以下是未来天气情况"."\n";
                                $future = $shuju["result"]["future"];
                                    foreach ($future as $k => $v) {
                                        $content .= date("Y-m-d", strtotime($v["date"])).":";
                                        $content .= $v["temperature"] . ",";
                                        $content .= $v["weather"] . ",";
                                        $content .= $v["direct"] ."\n";
                                    }
                            }else{//走到这里说明调用天气接口已经失败了，可以提示用户调用的方法
                                   //  尝试使用文本调用 机器人  // $text 用户所发的文本
                                 $text=$obj->Content;  //走到这里说明 格式不是   天气：地址，掉的不是天气接口  那就走机器人接口
                         $url="http://openapi.tuling123.com/openapi/api/v2";//请求地址
                         //  调用接口所需的数据
                         $data=[
                             "perception"=>[
                                 "inputText"=>[
                                     "text"=>$text
                                 ]
                             ],
                             "userInfo"=>[
                                 "apiKey"=>"b64cdee0866743ab95c03e2c225518e9",
                                 "userId"=>1
                             ]
                         ];
                         $shuju=json_encode($data);
                         $shuju=$this->http_post($url,$shuju);
                         $data=json_decode($shuju,true);
//                            file_put_contents("jqr.txt",$shuju["results"][0]["values"]["text"]);
                         if($data["intent"]["code"]!=5000) {
                             $content = $data["results"][0]["values"]["text"];
                         }else{
                             $content="";
                             $content.="机器人出错,可能是接口调用达到上限,请联系懒癌患者的管理员,手机号:13521378470 QQ 2397075084,报错了可回复,看到也是不回的(谨记)";
                             $content.="当前天气已开发,如果您想查看天气情况,请输入天气:地址(如 天气:邯郸,会给您返回邯郸当天及未来几天的天气情况.注意 “:”为英文的冒号,只支持国内天气查询)";

                         }
                            }
                    break;
                // 聊天机器人  图灵机器人
                    case "voice": //语音类型为 voice  // 在公众号中使用 语音  就调用 图灵机器人进行对话
                    // 使用测试号发送语音，  微信服务器给我们的服务器发送的 XML 数据中  会有 Recognition
                    //  它是将 我们所说的语音 转换为文字, 再调用 接口 发送给 图灵机器人, 使他回话.
                        //$content=$obj->Recognition;//说什么反什么
                        $text=$obj->Recognition;// 用户所说的内容 (微信服务器分析的);
                        $url="http://openapi.tuling123.com/openapi/api/v2";//请求地址
                        //  调用接口所需的数据
                        $data=[
                            "perception"=>[
                                "inputText"=>[
                                        "text"=>$text
                                ]
                             ],
                             "userInfo"=>[
                                 "apiKey"=>"605ae0fe45094384a5b0ef2bf98209ff",
                                 "userId"=>1
                             ]
                        ];
                            // 接口 要求 post的方式调用 所以使用封装的 curl的post的方法
                                $shuju=json_encode($data);
                                $shuju=$this->http_post($url,$shuju);
                                $data=json_decode($shuju,true);
//                            file_put_contents("jqr.txt",$shuju["results"][0]["values"]["text"]);
                            if($data["intent"]["code"]!=5000) {
                                $content = $data["results"][0]["values"]["text"];
                            }else{
                                $content="机器人出错,请联系懒癌患者的管理员,手机号:13521378470 QQ 2397075084,报错了可回复,看到也是不回的(谨记)";
                            }

                    break;
            }


            echo $this->xiaoxi($obj,$content);
        }
    }




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
    private  function writeLog($data){// 错误写入文件好找
        if(is_object($data) ||is_array($data)){
            $data=json_encode($data);
        }
        file_put_contents("aaa.txt",$data);die;


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
