<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class UserModel extends Model
{
   protected $table='wx_user'; 
   protected $primaryKey = 'user_id';
   //黑名单
   protected $guarded = [];
   

}
