# Unofficial VALORANT Authentication Library
A PHP library to obtain authentication tokens for VALORANT (it probably works for other Riot games too).

This library is still WIP, but it works at some point. If you need help to use it, don't hesitate to open an issue.

## How to install
```
composer require rainbowshaggy/ritoauth:dev-master
```

## Usage
I've reworked this library, so it can be used as easy as possible. Here's an example, how to use it.

```PHP
use RainbowShaggy\RitoAuth\RiotAuth;
use RainbowShaggy\RitoAuth\RiotAuthenticationException;

$riotAuth = new RiotAuth(["username" => "RiotUsername",
                          "password" => "RiotPassword"]);

try {
    if ($riotAuth->authenticate()) {
        print $riotAuth->accessToken;
        print $riotAuth->entitlements;
        print $riotAuth->tokenExpiration;
    }
} catch (RiotAuthenticationException $e) {
    print $e;
}
```

# TODO
Here are list of things I'd like to finish or improve in the future before 1.0 version. I'm using this library in my other project so I will improve it overtime. If you want to help, feel free to send a PR.
 - [ ] 2FA authentication
 - [ ] Tests
 - [ ] 

# Disclaimer
This library isn't endorsed or affilated by Riot Games in any way. All associated properties are trademarks or registered trademarks of Riot Games, Inc.

I am also not responsible for any activites and consequences to your Riot account this project will be used for. **Use it at your own risk!**

# License
RitoAuth is open-sourced software licensed under the [MIT license](LICENSE).