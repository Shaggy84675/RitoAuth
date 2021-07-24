# Valorant Client Wrapper
A php wrapper for authenticating user for Valorant Client APIs

## How to install
```
composer require weedeej/authspace:dev-master
```

## Usage
```PHP
$authObject = new Authentication("username","password","region");
$authTokens = $authObject->authByUsername();
```