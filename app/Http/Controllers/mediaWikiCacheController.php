<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\mediaWikiCache;

class mediaWikiCacheController extends Controller
{
    public function getUserInfo(string $username) {
		
		return mediaWikiCache::userInfo($username);
		
	}
}
