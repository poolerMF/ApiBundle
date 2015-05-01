<?php

namespace Plr\Bundle\ApiBundle\Plugin;

use Guzzle\Common\Event;
use Guzzle\Http\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiPlugin {

    protected $client;
    protected $config;
    protected $response;
    protected $container;

    public function __construct(Client $client, array $config = [], ContainerInterface $container) {
        $this->client = $client;
        $this->config = $config;
        $this->container = $container;
    }

    function callJSON($method, $params = [], $url = '') {

        $postData = $this->setData($method, $params, $url);
        $this->setCurlOptions();
        $logger = $this->container->get("logger");

        try {

            $request = $this->client->post($url);
            if (isset($this->config["user_agent"])) {
                $request->setHeader('user-agent', $this->config["user_agent"]);
            }
            $request->setBody(json_encode($postData));

            $request->getEventDispatcher()->addListener('request.complete', function (Event $e) {
                $response = $e["response"]->getBody(true);

                try {
                    $response = json_decode($response, true);
                } catch (\Exception $ee) {
                    $logger = $this->container->get("logger");
                    $logger->error($ee->getMessage());
                }

                $this->response = $response;
            });
            $request->send();
        } catch (\Exception $e) {
            $logger->error($e->getMessage());
        }

        if ($this->container->get("kernel")->getEnvironment() == "dev") {

            $a = $this->container->get("data_collector.plr_api");
            $a->collect(new Request(), new Response());
            $calls = $a->getCalls();

            $return = [];
            foreach ($calls as $call) {
                $a = [];

                if ($call["time"]["total"] > 1) {
                    $time = round($call["time"]["total"], 2) . " s";
                } else {
                    $time = round($call["time"]["total"] * 1000, 0) . " ms";
                }

                $a["response"] = rawurlencode($call["response"]["body"]);
                $a["request"] = rawurlencode($call["request"]["body"]);
                $a["error"] = $call["error"];
                $a["total_time"] = $call["time"]["total"];
                $a["header"] = rawurlencode($call["response"]["statusCode"] . " " . $call["response"]["reasonPhrase"] . " (" . $time . ")");
                $return[] = $a;
            }

            if(strlen(json_encode($return)) < 100000){
                setcookie("plr_api", json_encode($return), time() + (15), "/");
            }

            if ($this->config["log"]) {
                $logger = $this->container->get("logger");
                $logger->info("URL: " . $this->config["baseUrl"] . $url);
                $logger->info("DATA: " . json_encode($postData));
                $logger->info("RESPONSE: " . is_array($this->response) ? json_encode($this->response) : $this->response);
            }
        }

        return $this->response;
    }

    public function setData($method, $params, $url) {

        $defaultParameters = $this->config["parameters"];

        $postData = [];
        $postData["method"] = $method;

        if ($params) {
            $postData["params"] = $params;
        }

        $postData["id"] = (string) time();
        foreach ($defaultParameters as $key => $value) {
            $postData[$key] = (string) $value;
        }

        return $postData;
    }

    private function setCurlOptions() {

        if (isset($this->config["curl_options"])) {

            $clientConfig = $this->client->getConfig();
            $curlOptions = $clientConfig->get('curl.options');

            foreach ($this->config["curl_options"] as $key => $value) {
                $curlOptions[constant($key)] = $value;
            }
            $clientConfig->set('curl.options', $curlOptions);
        }
    }

}
