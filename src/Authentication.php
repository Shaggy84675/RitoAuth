<?php
namespace Weedeej\AuthSpace;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\ClientException;

use Weedeej\AuthSpace\Utils;

class Authentication {
    private $client;
    private $username;
    private $password;
    private $shard;
    private $remember;
    public $accessToken;

    public function __construct(Array $credentials = null){
        $this->client = new Client(array('cookies' => true,'http_errors' => false));
        if($credentials != null){
            if(!isset($credentials["password"])){
                $this->accessToken = $credentials["username"];
                $this->shard = $credentials["shard"];
            }else{
                $this->username = $credentials["username"];
                $this->password = $credentials["password"];
                $this->shard = $credentials["shard"];
                $this->remember = false;
                if(isset($credentials["remember"])){
                    if($credentials["remember"] == true){
                        $this->remember = true;
                    }
                }
            }
        }
            
    }

    public function reAuth(){
        if(isset($_COOKIE["ssid"]) && isset($_COOKIE["shard"])){
            $utils = new Utils();
            $reauth = CookieJar::fromArray([
                'ssid' => $_COOKIE["ssid"]
            ], 'auth.riotgames.com');

            $authResponse = $this->client->request("GET","https://auth.riotgames.com/authorize?redirect_uri=https%3A%2F%2Fplayvalorant.com%2Fopt_in&client_id=play-valorant-web-prod&response_type=token%20id_token", ["cookies"=>$reauth, "allow_redirects"=>false]);
            $location = $authResponse->getHeader("location")[0];
            $this->accessToken = $utils->getBetween("access_token=","&scope",$location);
            $entitlement = $this->getEntitlements($this->accessToken);

            return array("accessToken"=>$this->accessToken,
                        "entitlements_token"=>$entitlement,
                        "shard"=>$_COOKIE["shard"]);
        }
    }

    public function collectCookies(){
        $jar = new CookieJar();
        $postData = json_decode('{"client_id": "play-valorant-web-prod","nonce": "1","redirect_uri": "https://playvalorant.com/opt_in","response_type": "token id_token"}');
        $response = $this->client->request("POST", "https://auth.riotgames.com/api/v1/authorization", ["json"=>$postData,
                                                                                                 "cookies"=>$jar]);
        return $jar;
    }

    public function authUser(){
        $session = $this->collectCookies();
        $utils = new Utils();

        $postData = json_decode('{"type":"auth", "username":"'.$this->username.'", "password":"'.$this->password.'", "remember":'.json_encode($this->remember).'}');
        $response = $this->client->request("PUT","https://auth.riotgames.com/api/v1/authorization",["json"=>$postData,
                                                                                              "cookies"=>$session]);
        if($this->remember){
            setcookie("ssid",$session->getCookieByName("ssid")->getValue(),$session->getCookieByName("ssid")->getExpires(), "/");
            setcookie("shard",$this->shard,$session->getCookieByName("ssid")->getExpires(), "/");
        }
        if(isset(json_decode((string) $response->getBody(),true)["error"])){
            return json_decode((string) $response->getBody());
        }else{
            $this->accessToken = $utils->getBetween("access_token=","&scope",(string)$response->getBody());
            return $this->accessToken;
        }
    }

    public function getEntitlements(String $accessToken){
        $postData = json_decode('{}');
        $response = $this->client->request("POST","https://entitlements.auth.riotgames.com/api/token/v1",["json"=>$postData,
                                                                                                    "headers"=>["Authorization"=>"Bearer $accessToken"]]);
        return json_decode((string)$response->getBody())->entitlements_token;
    }
    
    public function authByUsername(){
        $session = $this->collectCookies();
        $authSession = $this->authUser();
        if(isset($authSession->error)){
            if($authSession->error == "auth_failure"){
                return "{\"error\":\"Invalid username or password\"}";
            }
            return "{\"error\":\"".$authSession->error."\"}";
            
        }
        $entitlement = $this->getEntitlements($this->accessToken);

        return array("accessToken"=>$this->accessToken,
                     "entitlements_token"=>$entitlement,
                     "shard"=>$this->shard);
    }

    public function authByToken(){
        $response = $this->getEntitlements($this->accessToken);
        if($response == null){
            return "{\"error\":\"You entered an expired or invalid token.\"}";
        }
        return array("accessToken"=>$this->accessToken,
                     "entitlements_token"=>$response,
                     "shard"=>$this->shard);
    }
}
?>