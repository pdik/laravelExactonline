<?php
namespace Pdik\laravelExactonline\Models;

use Illuminate\Database\Eloquent\Model;

class ExactSettings extends Model{
    protected $table = 'exact_settings';
    /**
     * @param $name
     * @param $value
     */
    public static function setValue($name, $value)
    {
        if(config('exact.type') =='multiuser'){
         //For future
        }elseif($name != "client_id" || $name != "client_secret" || $name != "webhook_secret"){
            $s= ExactSettings::where('option_name', $name)->firstOrnew();
            $s->option_name = $name;
            $s->option_value = $value;
            $s->save();
        }
    }

    /**
     * @param $name
     * @return mixed
     */
     public static function getValue($key, $default = null){
         if($key != "client_id" || $key != "client_secret" || $key != "webhook_secret"){
               return config('exact.'.$key.'');
          }else{
            $setting = ExactSettings::where('option_name', '=', $key)->first();
            if($setting){
                return $setting->option_value;
            }
            //When not in DB get default config value for one connection
            return $default;
         }
    }
}