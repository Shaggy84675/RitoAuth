<?php
namespace Weedeej\Tests;
use Weedeej\AuthSpace\Authentication;

class TestAuth {
    const tokenmethod = ["username"=>"token.fetched.by.my.other.repo",
                         "shard"=>"ap"];

    const pwdmethod = ["username"=>"myUsername69",
                       "password"=>"s3cuReP4ss420",
                       "shard"=>"ap"];

    const remember = ["username"=>"myUsername69",
                       "password"=>"s3cuReP4ss420",
                       "shard"=>"ap",
                       "remember"=>true];

    /*This will return Access Token(Bearer), 
    Entitlements Token, and Shard(Region) using Username and Password for fetching Data.
    */
    public function ByPasswordAuth(){
        $authObject = new Authentication(self::pwdmethod);
        $authTokens = $authObj->authByUsername();
        return $authTokens;
    }

    /*This will return the same thing but does not require
    Username or Password being entered on the field. 

    NOTE: The array that will be given to this function should NOT have the 'password' key.*/
    public function ByToken(){
        $authObject = new Authentication(self::tokenmethod);
        $authTokens = $authObj->authByToken();
        return $authTokens;
    }

    /*Remembering Cookies not credentials for Re-auth

    NOTE: "remember" should be set on true*/
    public function rememberMe(){
        $authObject = new Authentication(self::remember);
        $authTokens = $authObj->authByUsername();
        return $authTokens;
    }

    /* Parameters on Authentication object should be 'null' */
    public function reAuth(){
        $authObject = new Authentication();
        $authTokens = $authObj->reAuth();
        return $authTokens;
    }
}
?>