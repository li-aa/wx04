<?php
namespace App\Http\Services;
class Menu{
    public static function getMenuOrder($menu,$pid=0){
        $menuArr=[];
        foreach($menu as $key=>$val){
            if($val["menu_pid"]==$pid){
                $menuArr[]=$val;
                $menuArr=array_merge($menuArr,self::getMenuOrder($menu,$val["menu_id"]));
            }
        }
        return $menuArr;
    }
}
?>