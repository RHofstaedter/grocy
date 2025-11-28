<?php

namespace Grocy\Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\ExceptionRequestException;
use Psr\Http\Message\ResponseInterface;

class WebhookRunner
{
    public function __construct()
    {
        $this->client = new Client(['timeout' => 2.0]);
    }

    private readonly \GuzzleHttp\Client $client;

    public function run(string $url, $args, $json = false): void
    {
        $reqArgs = $json ? ['json' => $args] : ['form_params' => $args];
        try {
            file_put_contents('php://stderr', 'Running Webhook: ' . $url . "\n" . print_r($reqArgs, true));

            $this->client->request('POST', $url, $reqArgs);
        } catch (RequestException $requestException) {
            file_put_contents('php://stderr', 'Webhook failed: ' . $url . "\n" . $requestException->getMessage());
        }
    }

    public function runAll($urls, $args): void
    {
        foreach ($urls as $url) {
            $this->run($url, $args);
        }
    }
}
