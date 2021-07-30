# Valorant Client Wrapper
A php wrapper for authenticating user for Valorant Client APIs

## How to install
```
composer require weedeej/authspace:dev-master
```

## Usage

Auth using username and password
```PHP
use Weedeej\AuthSpace\Authentication;

$authObject = new Authentication(["username"=>"myUsername69",
                       		  "password"=>"s3cuReP4ss420",
                       		  "shard"=>"ap"]);
$authTokens = $authObject->authByUsername(); //Password is the most required key here.
```

Auth using Tokens
```PHP
use Weedeej\AuthSpace\Authentication;

$authObject = new Authentication(["username"=>"token.fetched.from.my.other.repo",
                         	  "shard"=>"ap"]); 
$authTokens = $authObject->authByToken();//If password is defined, 
					 //this will return an error.
```

Remembering Cookies
```PHP
/*Remembering Cookies not credentials for Re-auth

NOTE: "remember" should be set on true*/
public function rememberMe(){
    $authObject = new Authentication(self::tokenmethod);
    $authTokens = $authObj->authByToken();
    return $authTokens;
}

/* Parameters on Authentication object should be 'null' */
public function reAuth(){
    $authObject = new Authentication();
    $authTokens = $authObj->reAuth();
    return $authTokens;
}
```
To get the token required, There so many ways to get it. But I recommend my project
[VTACCESS](https://github.com/weedeej/val-token-fetcher) as it copies that token instead of being displayed.
# License
MIT

## Disclaimer
I am not responsible on any illegal activites this project will be used for.

Materials, Endpoints, and any other functions here in this repository are owned by Riot Games
and there is no way this project is affiliated or endorsed by Riot Games.