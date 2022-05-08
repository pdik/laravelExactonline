<?php

namespace Pdik\LaravelExactOnline\Models;

use Illuminate\Database\Eloquent\Model;

class ExactSettings extends Model
{
    protected $table = "settings";
    public $timestamps = false;
    protected $fillable = ['option_name', 'option_value', 'option_id'];
    protected $primaryKey = 'option_id';

   public static function setValue($name, $value)
    {
        $s= settings::where('option_name', $name)->firstOrnew();
        $s->option_name = $name;
        $s->option_value = $value;
        $s->save();
    }

    /**
     * @param $name
     * @return mixed
     */
     public static function getValue($key, $default = null){
        $setting = settings::where('option_name', '=', $key)->first();
        if($setting){
            return $setting->option_value;
        }
        return $default;
    }

}