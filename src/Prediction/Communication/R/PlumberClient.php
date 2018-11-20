<?php


namespace Prediction\Communication\R;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use PHPUnit\Util\Json;
use Zend\Diactoros\Response\JsonResponse;

class PlumberClient
{
    /**
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * @var secret for authentication
     */
    protected $secret;

    /**
     * @var string url of Plumber server
     */
    protected $url;

    /**
     * @var string username for authentication
     */
    protected $user;

    public function __construct($config)
    {
        if (isset($config['plumber'])) {
            $this->url = $config['plumber']['url'];
            $this->user = $config['plumber']['user'];
            $this->secret = $config['plumber']['secret'];
        }
    }

    protected function getClient()
    {
        if (!$this->client) {
            $this->client = new Client([
                'base_uri' => $this->url,
                'timeout' => 20,
            ]);
        }

        return $this->client;
    }



    public function request($endpoint, $method, $data)
    {
        $client = $this->getClient();

        try {
            $response = $client->request($method, $endpoint, [
                'json' => $data,
            ]);
        } catch(RequestException $e) {
            // return an error message as json
            $code = $e->getCode();
            if ($code == 0) {
                $code = 500;
            }
            $error = null;
            $hint = null;
            if ($e instanceof ConnectException) {
                $code = 503;
                $error = 'plumber_connection_error';
                $hint = 'could not connect to plumber server';
            }
            return new JsonResponse(['error' => $error, 'message' => $e->getMessage(), 'hint' => $hint, 'code' => $code], $code);
        }

        return $response;
    }
}