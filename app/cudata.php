<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class cudata extends Model
{
    protected $table = 'cuData';
	
	public function appeal() {
		return $this->belongsTo('App\Appeal');
	}
}
