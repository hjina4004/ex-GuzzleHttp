<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Promise;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

require_once '../vendor/autoload.php';

function requestPool()
{
    $client = new Client();

    $requests = function () use ($client) {
        $uris = [
            'https://httpbin.org/get',
            'https://httpbin.org/delay/1',
            'https://httpbin.org/delay/2',
            'https://httpbin.org/get?q=foo',
        ];
        for ($i = 0; $i < count($uris); $i++) {
            $uri = $uris[$i];
            yield function () use ($client, $uri) {
                return $client->getAsync($uri);
            };
        }
    };

    $pool = new Pool($client, $requests(), [
        'concurrency' => 4,
        'fulfilled' => function (Response $response, $index) {
            // this is delivered each successful response
            echo $index . "] fulfilled<br>";
            echo $response->getBody()->getContents() . "<br>";
        },
        'rejected' => function (RequestException $reason, $index) {
            // this is delivered each failed request
            echo $index . "] rejected " . $reason->getMessage() . "<br>";
        },
    ]);

    // Initiate the transfers and create a promise
    $promise = $pool->promise();

    // Force the pool of requests to complete.
    $promise->wait();
}

function requestPromise()
{
    $client = new Client();

    $promises = [
        $client->requestAsync('GET', 'https://httpbin.org/get'),
        $client->requestAsync('GET', 'https://httpbin.org/delay/1'),
        $client->requestAsync('GET', 'https://httpbin.org/delay/2'),
        $client->requestAsync('GET', 'https://httpbin.org/get?q=foo'),
    ];

    // Wait for the requests to complete; throws a ConnectException
    // if any of the requests fail
    $responses = Promise\Utils::unwrap($promises);

    // Wait for the requests to complete, even if some of them fail
    $responses = Promise\Utils::settle($promises)->wait();

    for ($i = 0; $i < count($promises); $i++) {
        echo $responses[$i]['value']->getBody()->getContents() . "<br>";
    }
}

function requestAsync()
{
    $client = new Client();

    $promise = $client->requestAsync('GET', 'http://httpbin.org/get');
    $promise->then(
        function (ResponseInterface $res) {
            echo $res->getBody()->getContents() . "<br>";
        },
        function (RequestException $e) {
            echo $e->getMessage() . "<br>";
        }
    );
    $promise->wait();
}

requestPool();
// requestPromise();
// requestAsync();