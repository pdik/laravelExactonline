<?php

namespace Pdik\LaravelExactOnline\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Pdik\LaravelExactOnline\Services\Exact;
class Settings extends Model
{
    use HasFactory;
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    public $timestamps = false;
    protected $fillable = ['option_name', 'option_value','option_id'];
    protected $primaryKey = 'option_id';
        /**
     * @param $name
     * @param $value
     */
    public static function setValue($name, $value)
    {
        $s= Settings::where('option_name', $name)->firstOrnew();
        $s->option_name = $name;
        $s->option_value = $value;
        $s->save();
    }

    /**
     * @param $name
     * @return mixed
     */
     public static function getValue($key, $default = null){
        $setting = Settings::where('option_name', '=', $key)->first();
        if($setting){
            return $setting->option_value;
        }
        return $default;
    }


}
