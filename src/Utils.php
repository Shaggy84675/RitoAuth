<?php
namespace RainbowShaggy\RitoAuth;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

class Utils {                             
    public function getBetween($start, $end, $str){
        return explode($end,explode($start,$str)[1])[0];
    }

    public function getRegion(Client $client, String $accessToken){
        $response = $this->client->request("PUT","https://riot-geo.pas.si.riotgames.com/pas/v1/product/valorant",["json"=>['id_token' => $this->idToken], "headers"=>["Authorization"=>"Bearer $accessToken"]]);
        $this->shard = json_decode((string)$response->getBody())->affinities->live;
        return json_decode((string)$response->getBody())->affinities->live;
    }

    public static function GenerateUrlSafeToken($length) : string {
        $bytes = random_bytes($length);
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    public static function ParseUrlQuery(string $url): object {
        $parsedUrl = parse_url($url);
    
        if (isset($parsedUrl['fragment'])) {
            parse_str($parsedUrl['fragment'], $out);
            return (object) $out;
        } else {
            // Handle the case when there is no fragment part in the URL
            return (object) array();
        }
    }

    public static function ParseAuthResponse(ResponseInterface $response) : object {
        $json = json_decode((string)$response->getBody());
        if (isset($json->error)) {
            return $json;
        }

        return (object) Utils::ParseUrlQuery($json->response->parameters->uri);
    }

    public static function GetVersion(Client $client) : object {
        $response = $client->get('https://valorant-api.com/v1/version');
        return json_decode($response->getBody()->getContents())->data;
    }

    public function ezDec($obj){
        if(gettype($obj) == "string") return json_decode($obj);
        if(gettype($obj) == "array") return json_encode($obj, JSON_FORCE_OBJECT);
    }

}
?>