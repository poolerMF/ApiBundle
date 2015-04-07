# PlrAPI Bundle

Provide a basic logger of API request in toolbar.

This bundle is extension of [playbloom/guzzle-bundle](https://github.com/ludofleury/GuzzleBundle)

## Installation

``` bash
$ php composer.phar require plr/api-bundle "1.*"
```

## Enable the Bundle
``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
   $bundles = array(
       // ...
       new Playbloom\Bundle\GuzzleBundle\PlaybloomGuzzleBundle();
       new Plr\Bundle\ApiBundle\PlrApiBundle(),
   );
}
```

## Configure the Bundle
``` yaml
plr_api:
    server1:
        log: true
        baseUrl: "http://www.example.com"
        parameters:
            jsonrpc: "2.0"
            custom_parameter: "11"
            ...
        user_agent: "Plr API"
        curl_options:
            CURLOPT_CONNECTTIMEOUT: 10
            CURLOPT_SSL_VERIFYPEER: false
            CURLOPT_SSLVERSION: 3
            ...
    server2:
        log: false
        ...
```

## Example of usage
``` php
$response = $this->get('api.server1')->callJSON("client_login", ["login"=>"user", "password"=>"password"]);
echo $response["result"];

$response = $this->get('api.server1')->callJSON("client_login", [], "client.php");
```

## Licence

This bundle is under the MIT license. See the complete license in the bundle