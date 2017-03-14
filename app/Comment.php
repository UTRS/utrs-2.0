<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{

	protected $fillable = ['comment'];

    public function appeal() {
	
		return $this->belongsTo('App\Appeal');
		
	}
    public function user() {
	
		return $this->belongsTo('App\User');
		
	}
}
