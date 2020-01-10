<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;
    //
    protected $table = 'users';
    protected $forcedNullStrings = ['name_ar', 'name_en', 'address_ar', 'address_en', 'mobile', 'email', 'tall', 'weight', 'birth_date', 'device_token', 'activation_code', 'photo', 'api_token'];
    protected $casts = [
        'status' => 'integer',
    ];

    protected $fillable = [
        'name_ar', 'name_en', 'address_ar', 'address_en', 'mobile', 'academy_id', 'team_id', 'email', 'tall', 'weight', 'birth_date', 'status', 'device_token',
        'activation_code', 'photo', 'api_token', 'password', 'created_at', 'updated_at'];

    protected $hidden = [
        'updated_at', 'password'
    ];


    public function academy()
    {
        return $this->belongsTo('App\Models\Academy', 'academy_id', 'id');
    }

    public function Coaches()
    {
        return $this->belongsToMany('App\Models\Coach', 'users_coaches', 'user_id', 'coach_id');
    }

    public function team()
    {
        return $this->belongsTo('App\Models\Team', 'team_id', 'id');
    }


    public function getPhotoAttribute($val)
    {
        return ($val != "" ? asset($val) : "");
    }

    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->forcedNullStrings) && $value === null)
            $value = "";

        return parent::setAttribute($key, $value);
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function setPasswordAttribute($password)
    {
        if (!empty($password)) {
            $this->attributes['password'] = bcrypt($password);
        }
    }

    public function tokens()
    {
        return $this->hasMany('App\Models\UserToken');
    }


    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function getStatusAttribute($status)
    {
        return $status == 0 ? 'غير مفعل' : 'مفعل';
    }

    public function scopeSelection($query)
    {
        return $query->select('id', 'name_ar', 'name_en', 'address_ar', 'address_en', 'mobile', 'email', 'tall', 'weight', 'birth_date', 'status', 'academy_id', 'team_id', 'device_token', 'photo');
    }

    public function tickets()
    {
        return $this->morphMany('\App\Models\Ticket', 'ticketable');
    }

    public function notifications()
    {
        return $this->morphMany('\App\Models\Notification', 'notificationable');
    }
}
