<?php
namespace RainbowShaggy\RitoAuth;

use DateInterval;
use DateTime;
use DateTimeZone;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

use RainbowShaggy\RitoAuth\Utils;

class RiotAuthenticationException extends Exception { }

class RiotAuth {
    private $client;
    private $username;
    private $password;
    public $accessToken;
    public $response;
    public $entitlements;
    public $tokenExpiration;
    private $ssid;
    private $clid;
    private $csid;
    private $address;

    private $headers = [
        "Accept-Encoding" => "gzip, deflate, br",
        'Content-Type' => 'application/json',
        'User-Agent' => 'RiotClient/64.0.10.5036631.232 rso-auth (Windows;10;;Professional, x64)',
        'Host' => 'auth.riotgames.com',
        'Accept-Language' => 'en-US,en;q=0.9',
    ];


    public function __construct(Array $credentials = null) {
        $this->client = new Client(
            array('curl' => [CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_3],
                  'cookies' => true,
                  'http_errors' => false,
                  'verify' => false
            )
        );

        //$this->setRiotClientVersion(Utils::GetVersion($this->client)->riotClientBuild);

        //dd($this->headers);
        
        $this->address = "auth.riotgames.com";
        $this->username = $credentials["username"] ?? "";
        $this->password = $credentials["password"] ?? "";
    }

    public function setUserAgent(string $ua) {
        $this->headers["User-Agent"] = $ua;
    }

    public function setHeaders(array $headers) {
        $this->headers = $headers;
    }

    public function getHeaders() {
        return $this->headers;
    }

    public function setRiotClientVersion(string $version) {
        $ua = $this->headers["User-Agent"];
        $this->headers["User-Agent"] = $version . substr($ua, strpos($ua, ' '));
    }

    public function reAuth() : bool {
        if (!isset($_COOKIE["ssid"])) {
            return false;
        }

        $reauth = CookieJar::fromArray([
            'ssid' => $_COOKIE["ssid"]
        ], $this->address);

        $urlData = json_decode('{
            "client_id": "riot-client",
            "nonce": "'.Utils::GenerateUrlSafeToken(16).'",
            "redirect_uri": "http://localhost/redirect",
            "response_type": "id_token token",
            "scope": "account link openid"
        }');

        $authResponse = $this->client->request("GET","https://".$this->address."/authorize?".http_build_query($urlData, "", null,  PHP_QUERY_RFC3986), ["cookies" => $reauth, "allow_redirects" => false]);
        $location = $authResponse->getHeader("location")[0];
        $response = Utils::ParseUrlQuery($location);

        if (!isset($response->access_token)) {
            return false;
        }

        $this->response = $response;
        $this->accessToken = $response->access_token;

        return true;
    }

    private function collectCookies() {
        $jar = new CookieJar();
        $postData = json_decode('{
            "client_id": "riot-client",
            "nonce": "'.Utils::GenerateUrlSafeToken(16).'",
            "redirect_uri": "http://localhost/redirect",
            "response_type": "id_token token",
            "scope": "account link openid"
        }');
        $this->client->request("POST", "https://".$this->address."/api/v1/authorization", ["json" => $postData, "cookies" => $jar, "headers" => $this->headers]);
        return $jar;
    }

    public function generateTokens() : bool {
        $session = $this->collectCookies();

        $postData = json_decode('{
            "type":"auth",
            "username":"'.$this->username.'",
            "password":"'.$this->password.'",
            "remember":"false"
        }');

        $response = $this->client->request("PUT","https://".$this->address."/api/v1/authorization", ["json" => $postData, "cookies" => $session, "headers" => $this->headers]);
        $authResponse = Utils::ParseAuthResponse($response);

        if (isset($authResponse->error)) {
            switch ($authResponse->error) {
                case 'auth_failure':
                    throw new RiotAuthenticationException('Failed to authenticate. Make sure username and password are correct. (Error message: '.$authResponse->error.')');
                    break;

                default:
                    throw new Exception('Error message: '. $authResponse->error.')');
                    break;
            }
        }
        
        /*if (json_decode((string)$response->getBody())->type == "multifactor")
        {
            setcookie("asid",$session->getCookieByName("asid")->getValue(),$session->getCookieByName("asid")->getExpires(), "/");

            return "2FA";
        }
        
        //2FA
        if($this->remember){
            setcookie("csid",$session->getCookieByName("csid")->getValue(),$session->getCookieByName("csid")->getExpires(), "/");
            setcookie("clid",$session->getCookieByName("clid")->getValue(),$session->getCookieByName("clid")->getExpires(), "/");
            setcookie("ssid",$session->getCookieByName("ssid")->getValue(),$session->getCookieByName("ssid")->getExpires(), "/");
            setcookie("shard",$this->shard,$session->getCookieByName("ssid")->getExpires(), "/");
            $this->ssid = $session->getCookieByName("ssid")->getValue();
            $this->csid = $session->getCookieByName("csid")->getValue();
            $this->clid = $session->getCookieByName("clid")->getValue();
        }*/

        setcookie("csid", $session->getCookieByName("csid")->getValue(), $session->getCookieByName("csid")->getExpires(), "/");
        setcookie("clid", $session->getCookieByName("clid")->getValue(), $session->getCookieByName("clid")->getExpires(), "/");
        setcookie("ssid", $session->getCookieByName("ssid")->getValue(), $session->getCookieByName("ssid")->getExpires(), "/");

        $this->response = $authResponse;
        $this->accessToken = $authResponse->access_token ?? null;

        $this->ssid = $session->getCookieByName("ssid")->getValue();
        $this->csid = $session->getCookieByName("csid")->getValue();
        $this->clid = $session->getCookieByName("clid")->getValue();

        //dump((string)$response->getBody());

        return true;
    }

    public function requestMfa($code)
    {
        $cookieJar = CookieJar::fromArray([
            'asid' => $_COOKIE['asid']
        ], 'auth.riotgames.com');

        $utils = new Utils();
        $putData = json_decode('{"type":"multifactor", "code":"'.$code.'", "rememberDevice":true}');
        $mfaResponse = $this->client->request("PUT","https://auth.riotgames.com/api/v1/authorization",["json"=>$putData, "cookies"=>$cookieJar, "headers"=>$this->headers]);
        if(isset(json_decode((string) $mfaResponse->getBody(),true)["error"])) return json_decode((string) $mfaResponse->getBody());
        setcookie("ssid",$cookieJar->getCookieByName("ssid")->getValue(),$cookieJar->getCookieByName("ssid")->getExpires(), "/");
        setcookie("shard",$this->shard,$cookieJar->getCookieByName("ssid")->getExpires(), "/");
        if($this->remember){
            setcookie("csid",$cookieJar->getCookieByName("csid")->getValue(),$cookieJar->getCookieByName("csid")->getExpires(), "/");
            setcookie("clid",$cookieJar->getCookieByName("clid")->getValue(),$cookieJar->getCookieByName("clid")->getExpires(), "/");
            setcookie("ssid",$cookieJar->getCookieByName("ssid")->getValue(),$cookieJar->getCookieByName("ssid")->getExpires(), "/");
            setcookie("shard",$this->shard,$cookieJar->getCookieByName("ssid")->getExpires(), "/");
            $this->ssid = $cookieJar->getCookieByName("ssid")->getValue();
            $this->csid = $cookieJar->getCookieByName("csid")->getValue();
            $this->clid = $cookieJar->getCookieByName("clid")->getValue();
        }
        $this->accessToken = $utils->getBetween("access_token=","&scope",(string)$mfaResponse->getBody());
        $this->idToken = $utils->getBetween("id_token=","&token_type",(string)$mfaResponse->getBody());
        return $this->accessToken;
    }

    private function getEntitlements(String $accessToken) {
        $postData = json_decode('{}');
        $response = $this->client->request("POST","https://entitlements.auth.riotgames.com/api/token/v1", ["json" => $postData, "headers" => ["Authorization"=>"Bearer ".$accessToken]]);
        $this->entitlements = json_decode((string)$response->getBody())->entitlements_token;
        return $this->entitlements;
    }

    public function authenticate(bool $useSessions = true): bool {
        if ($useSessions) {
            session_start();
        }

        $curDate = new DateTime("now", new DateTimeZone("UTC"));

        //If the token is still valid, there's no point to generate a new one.
        if (isset($_SESSION["token_expiration"]) && $_SESSION["token_expiration"] > $curDate) {
            $this->response = (object) $_SESSION;
            $this->accessToken = $this->response->access_token;
            $this->tokenExpiration = $this->response->token_expiration;
            $this->entitlements = $this->response->entitlements;
            //dump($this->response);
            return true;
        }
        
        // Try to authenticate using Reauth cookie first
        if (!$this->reAuth()) {
            if (!$this->generateTokens()) {
                return false;
            }
        }

        $this->tokenExpiration = $curDate->add(new DateInterval("PT".$this->response->expires_in."S"));
        
        $this->getEntitlements($this->accessToken);

        $returnArr = array("access_token"       => $this->accessToken,
                           "token_expiration"   => $this->tokenExpiration,
                           "entitlements"       => $this->entitlements);

        if(isset($this->ssid)){
            $returnArr["ssid"] = $this->ssid;
            $returnArr["csid"] = $this->csid;
            $returnArr["clid"] = $this->clid;
        }

        $_SESSION = $returnArr;
        return true;
    }
}
?>