<?php
namespace Weedeej\Tests;
use Weedeej\AuthSpace\Authentication;

class TestAuth {
    const username = "username";
    const password = "password";
    const shard = "ap";

    /*This will return Access Token(Bearer), 
    Entitlements Token, and Shard(Region) for fetching Data.
    */
    public function Auth(){
        $authObject = new Authentication(self::username,self::password,self::ap);
        $authTokens = $authObj->authByUsername();
        return $authTokens;
        
    }
}
?>