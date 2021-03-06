<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';
    public $timestamps = true;

    protected $fillable = [
        'title_ar',
        'title_en',
        'content_ar',
        'content_en',
        'academy_id',
        'mobile',
        'email',
        'latitude',
        'longitude',
        'address'
    ];

    protected $forcedNullStrings = [
        'title_ar',
        'title_en',
        'content_ar',
        'content_en',
        'mobile',
        'email',
        'latitude',
        'longitude',
        'address'
    ];


    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->forcedNullStrings) && $value === null)
            $value = "";
        return parent::setAttribute($key, $value);
    }

    public function getLatitudeAttribute($val)
    {
        return $val === null ? "" : $val;
    }

    public function getLongitudeAttribute($val)
    {
        return $val === null ? "" : $val;
    }

    public function getAddressAttribute($val)
    {
        return $val === null ? "" : $val;
    }
}
