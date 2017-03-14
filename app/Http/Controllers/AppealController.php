<?php

namespace App\Http\Controllers;

use Auth;
use DB;
use App\Appeal;
use App\Comment;
use App\mediaWikiCache;
use Illuminate\Http\Request;

class AppealController extends Controller
{


	protected $status = array(
		/**
		* The appeal has not yet passed email verification
		*/
		'STATUS_UNVERIFIED' => 'UNVERIFIED',
		/**
		* The appeal has been marked invalid by a developer
		*/
		'STATUS_INVALID' => 'INVALID',
		/**
		* The appeal is new and has not yet been addressed
		*/
		'STATUS_NEW' => 'NEW',
		/**
		* A response has been sent to the user, and a reply is expected
		*/
		'STATUS_AWAITING_USER' => 'AWAITING_USER',
		/**
		* The appeal needs to be reviewed by a tool admin
		*/
		'STATUS_AWAITING_ADMIN' => 'AWAITING_ADMIN',
		/**
		* The appeal needs to be reviewed by a checkuser before it can proceed
		*/
		'STATUS_AWAITING_CHECKUSER' => 'AWAITING_CHECKUSER',
		/**
		* The appeal needs to be reviewed by OPP before it can proceed
		*/
		'STATUS_AWAITING_PROXY' => 'AWAITING_PROXY',
		/**
		* The user has replied to a response, and the appeal is ready for further
		* action from the handling administrator
		*/
		'STATUS_AWAITING_REVIEWER' => 'AWAITING_REVIEWER',
		/**
		* The appeal needs to be reviewed by a WMF Staff member
		*/
		'STATUS_AWAITING_STAFF' => 'AWAITING_STAFF',
		/**
		* The appeal is on hold
		*/
		'STATUS_ON_HOLD' => 'ON_HOLD',
		/**
		* The appeal in question has been resolved
		*/
		'STATUS_CLOSED' => 'CLOSED',
	);
	/**
	* Email blacklist in regex form
	*/
	public static $EMAIL_BLACKLIST = '~@(wiki(p|m)edia|mailinator)~';

    public function index() {
		return view('Appeal.applicants.index');
	}
	
	public function create(Request $request) {
	
		$appeal = new Appeal($request->all());
		
		$appeal->save();
		
		return back();
	
	}
	
	public function view(Appeal $appeal) {
	
		$appeal->load('comments.user');
		
		$emailTemplates = DB::table('templates')->select('name', 'id')->get();
	
		return view('Appeal.reviewers.view', array(
			"appeal"			=> $appeal,
			"emailTemplates"	=> $emailTemplates,
		));
	
	}
	
    public function addComment(Appeal $appeal, Request $request) {
	
		$this->validate($request, [
			'comment'	=> 'required|max:10000',
		]);
		
		$comment = new Comment($request->all());
				
		$comment->user_id = Auth::user()->id;
				
		$comment = $appeal->comments()->save($comment);
		
		return $comment->load('user');
		
	}
	
	public function statusChange(Appeal $appeal, Request $request) {
	
		$this->validate($request, [
			'status'	=> 'required|in:' . implode(",", $this->status),
		]);
				
		$appeal->status = $request->input('status');
		
		$appeal->save();
		
		//TO DO: Do I really want to send the whole appeal object back to the user?
		return $appeal;
	
	}
}
