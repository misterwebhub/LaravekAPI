<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Jobs\UpdatePersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    public static function findToken($token)
    {
        [$id, $token] = explode('|', $token, 2);
        $token = Cache::remember("PersonalAccessToken::Model:$id", 600, function () use ($token) {
            return parent::findToken($token) ?? '_null_';
        });
        if ($token === '_null_') {
            return null;
        }
        return $token;
    }
   
   public function getTokenableAttribute()
    {
        return Cache::remember("PersonalAccessToken::{$this->id}::tokenable", 600, function () {
            return parent::tokenable()->first();
        });
    }
  
  
  public static function boot()
    {
        parent::boot();
        static::updating(function (self $personalAccessToken) {
            try {
                Cache::remember("PersonalAccessToken::lastUsgeUpdate", 3600, function () use ($personalAccessToken) {
                    dispatch(new UpdatePersonalAccessToken($personalAccessToken, $personalAccessToken->getDirty()));
                    return now();
                });
            } catch (Exception $e) {
                Log::critical($e->getMessage());
            }
            return false;
        });
        
        static::created(function (self $personalAccessToken) {
        		$id = $personalAccessToken->id;
            Cache::remember("PersonalAccessToken::Model:$id", 600, function () use ($personalAccessToken) {
            return $personalAccessToken ?? '_null_';
        		});
            return true;
        });
        
    }
    
    

}
