<?php

namespace App\Http\Controllers\Auth;

use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

use Socialite;

class LoginController extends Controller
{
    use AuthenticatesUsers;
    /**
     * Redirect the user to the English Wikipedia authentication page.
     *
     * @return Response
     */
    public function redirectToProvider()
    {
        return Socialite::driver('wikipedia')->redirect();
    } 

    /**
     * Obtain the user information from English Wikipedia.
     *
     * @return Response
     */
    public function handleProviderCallback()
    {
        $oauth = Socialite::driver('wikipedia')->user();

        $user = User::updateOrCreate(['oauth' => $oauth->token], ['name' => $oauth->username]);

        redirect()->route('dashboard');

    }
}