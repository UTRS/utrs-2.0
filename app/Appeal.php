<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Appeal extends Model
{
    protected $fillable = ['email', 'hasAccount', 'wikiAccountName', 'autoblock', 'message', 'edits', 'reason', 'info'];
	
	public function comments() {
	
		return $this->hasMany('App\Comment');
		
	}
	
	public function cudata() {
	
		return $this->hasOne('App\cudata');
		
	}

}
