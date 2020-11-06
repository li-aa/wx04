<?php
namespace App\Http\Services;
class Curl{
    //curl模拟发送get请求
    public static function http_get($url){
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); //设置请求的网址
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); //将请求的数据
        $output = curl_exec($curl);     //返回api的json对象
        curl_close($curl);
        return $output;
    }
    //curl模拟发送post请求
    public static function http_post($url,$data){
        $curl = curl_init(); //初始化curl
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 1);//使用的是post请求方式
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data); //post方式发送的数据
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);//不需要输出
        //然后将响应结果存入变量
        $output = curl_exec($curl);
        //关闭这个curl会话资源
        curl_close($curl);
        return $output;
    }
}
?>