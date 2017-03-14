<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Mediawiki\Api\MediawikiApi;
use Mediawiki\Api\ApiUser;
use Mediawiki\Api\FluentRequest;
use Carbon\Carbon;

class mediaWikiCache extends Model
{
	private   $api;
	private   $source;
	protected $url			= 'https://en.wikipedia.org/w/api.php';
	protected $table		= 'mediawiki_cache';
	protected $fillable 	= ['username', 'cache'];
    protected $primaryKey	= 'username';
    protected $appends		= array('source');
	public    $incrementing	= false;

	
	public function __construct() {
        $this->api = new MediawikiApi( $this->url );
		$this->api->login(
			new ApiUser(
				\Config::get('mediawiki.username'),
				\Config::get('mediawiki.password')
			)
		);
	}
	
	public function buildRequest() {
		return FluentRequest::factory();
	}
	
    public function getSourceAttribute()
    {
        return $this->source;  
    }
	
	public function get_request(FluentRequest $request) {
		return $this->api->getRequest( $request );
	}
	
	/**
	 * Determines where to seek user.
	 * @param string $username
	 * @return array
	 */
	public static function userInfo(string $username, $force = false) {
	
		$hrsOneWeek = 168; //168 hours in 1 week
		
		$cache = mediaWikiCache::find($username);
		
		if ($force
			|| ($cache) == null
			|| !isset($cache->updated_at)
			|| ($cache->calcHoursDiff($cache->updated_at) > $hrsOneWeek)
			) {
				//Delete existing cache if it exists
				if ($cache) {
					$cache->delete();
				}
				//Create a new cache
				$cache = new mediaWikiCache;
				//Get the data
				return $cache->cacheUser($username);
		} else {
			//Return cache from the database
			$cache->source = 'db';
			return $cache;
		}
	}
	
	/**
	 * Aquires information from the mediaWiki API and stores it in the database
	 * @param string $username
	 * @return array
	 */
	private function cacheUser(string $username) {
		
		$ipv4 = '/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/';
		$ipv6 = '/(([0-9a-fA-F]{1,4}:){7,7}[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,7}:|([0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,5}(:[0-9a-fA-F]{1,4}){1,2}|([0-9a-fA-F]{1,4}:){1,4}(:[0-9a-fA-F]{1,4}){1,3}|([0-9a-fA-F]{1,4}:){1,3}(:[0-9a-fA-F]{1,4}){1,4}|([0-9a-fA-F]{1,4}:){1,2}(:[0-9a-fA-F]{1,4}){1,5}|[0-9a-fA-F]{1,4}:((:[0-9a-fA-F]{1,4}){1,6})|:((:[0-9a-fA-F]{1,4}){1,7}|:)|fe80:(:[0-9a-fA-F]{0,4}){0,4}%[0-9a-zA-Z]{1,}|::(ffff(:0{1,4}){0,1}:){0,1}((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])|([0-9a-fA-F]{1,4}:){1,4}:((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9]))/';
		
		$named = (preg_match($ipv4, $username) == 1 || preg_match($ipv6, $username) == 1) ? false : true;
		
		if ($named) {
			$info = $this->parseNamedAcct($username, $this->get_user_info($username))->save();
		} else {
			$info = $this->parseAnonAcct($username, $this->get_anon_info($username))->save();
		}
		
		if ($info) {
			return $this;
		} else {
			return null;
		}
		
	}
	
	/**
	 * Parses the mediaWiki data
	 * @param string $username
	 * @param string $info
	 * @return array
	 */
	private function parseNamedAcct(string $username, array $info) {
		
		$this->username		= $username;
		
		if (isset($info["query"]["users"][0]["blockid"])) {
			$curr_block_info = array(
				"blockid"			=> $info["query"]["users"][0]["blockid"],
				"blockedby"			=> $info["query"]["users"][0]["blockedby"],
				"blockedtimestamp"	=> $info["query"]["users"][0]["blockedtimestamp"],
				"blockreason"		=> $info["query"]["users"][0]["blockreason"],
				"blockexpiry"		=> $info["query"]["users"][0]["blockexpiry"]
			);
		} else {
			$curr_block_info = null;
		}
		
		$this->cache = json_encode(array(
				'sysop'			=> (in_array('sysop', $info["query"]["users"][0]["groups"])) ? 1 : 0,		//1 for sysop, 0 for not
				'oversighter'	=> (in_array('checkuser', $info["query"]["users"][0]["groups"])) ? 1 : 0,	//1 for checkuser, 0 for not
				'checkuser'		=> (in_array('oversighter', $info["query"]["users"][0]["groups"])) ? 1 : 0,	//1 for oversighter, 0 for not
				'staff'			=> (in_array('staff', $info["query"]["users"][0]["groups"])) ? 1 : 0,		//1 for staff, 0 for not
				'edits'			=> $info["query"]["users"][0]["editcount"],									//Edit count
				'registration'	=> $info["query"]["users"][0]["registration"],								//Reg date
				'lastedit'		=> $info["query"]["usercontribs"][0]["timestamp"],							//User/Admin's last edit
				'blockinfo'		=> $curr_block_info,														//Need to get current block
				'blocklog'		=> $this->get_block_info($username),										//Block log
		));	
		
		$this->source		= 'api';
			
		return $this;
	}
	
	private function parseAnonAcct($username, array $info) {
	
		$this->username		= $username;
		
		//TO DO - CHECK THAT I AM W/IN THE BLOCK DATE & EXPIRY
		if (isset($info["query"]["blocks"][0]["id"])) {
			$curr_block_info = array(
				"blockid"			=> $info["query"]["blocks"][0]["id"],
				"blockedby"			=> $info["query"]["blocks"][0]["by"],
				"blockedtimestamp"	=> $info["query"]["blocks"][0]["timestamp"],
				"blockreason"		=> $info["query"]["blocks"][0]["reason"],
				"blockexpiry"		=> $info["query"]["blocks"][0]["expiry"]
			);
		} else {
			$curr_block_info = null;
		}
		
		$this->cache = json_encode(array(
				'sysop'			=> 0,
				'oversighter'	=> 0,
				'checkuser'		=> 0,
				'staff'			=> 0,
				'edits'			=> 0, //Will get later $info["query"]["users"][0]["editcount"],									//Edit count
				'registration'	=> null, 																	//Anons dont have a reg date
				'lastedit'		=> $info["query"]["usercontribs"][0]["timestamp"],							//User/Admin's last edit
				'blockinfo'		=> $curr_block_info,														//Need to get current block
				'blocklog'		=> $this->get_block_info($username),										//Block log
		));	
		
		$this->source		= 'api';
			
		return $this;
	}
	
	/**
	 * Gets the user info from mediaWiki API
	 * @param string $username
	 * @return array
	 */
	private function get_user_info(string $username) {
		return $this->api->getRequest( $this->buildRequest()
			->setAction('query' )
			->setParam('list', 'users|usercontribs' )
			->setParam('usprop', 'groups|editcount|registration|blockinfo' )
			->setParam('ususers', $username)
			->setParam('uclimit', 1)
			->setParam('ucuser', $username)
			->setParam('ucdir', 'older')
		);
	}
	
	private function get_anon_info(string $username) {
		return $this->api->getRequest( $this->buildRequest()
			->setAction('query' )
			->setParam('list', 'blocks|usercontribs' )
			->setParam('bkusers', $username)
			->setParam('ucuser', $username)
			->setParam('uclimit', 1)
			->setParam('ucdir', "older")
		);
	}
	
	/**
	 * Gets the block info from mediaWiki API
	 * @param string $username
	 * @return array
	 */
	private function get_block_info(string $username) {
		return $this->api->getRequest( $this->buildRequest()
			->setAction( 'query' )
			->setParam( 'list', 'logevents' )
			->setParam('letitle', 'User:' . $username )
			->setParam('letype', 'block')
		);
	}
	
	/**
	 * Calculates hours since a given date
	 * @param string $username
	 * @return array
	 */
	private function calcHoursDiff(Carbon $date) {
	
		$now = new Carbon;
		
		$diff = date_diff($now, $date);
	
		$hoursFromDays = $diff->days * 24;
		
		return $diff->h + $hoursFromDays;
	
	}
	
	/**
	 * Logs out of mediaWiki API
	 * @return array
	 */
	public function __destruct() {
		$this->api->logout();
	}
}