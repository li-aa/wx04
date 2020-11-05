<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Redis;
class TextController extends Controller
{
    public function aa(){
    	// $c = DB::table('p_users')->limit(3)->get();
    	// dd($c);
    	$k = '04';
    	Redis::set($k,time());
    	$cc = Redis::get($k);
    	dd($cc);
    }
}
