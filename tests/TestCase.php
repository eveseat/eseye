<?php

namespace Seat\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;

class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \GuzzleHttp\Handler\MockHandler
     */
    protected static MockHandler $http_feed_handler;

    protected static Client $http_client;

    protected static array $request_logs;

    /**
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        self::$http_feed_handler = self::$http_feed_handler ?? new MockHandler();
        self::$request_logs = self::$request_logs ?? [];

        $history = Middleware::history(self::$request_logs);

        $stack = HandlerStack::create(self::$http_feed_handler);
        $stack->push($history);

        self::$http_client = self::$http_client ?? new Client([
            'handler' => $stack,
        ]);
    }

    public static function tearDownAfterClass(): void
    {
        echo '____________________________________________________' . PHP_EOL;
        echo get_called_class() . PHP_EOL;
        echo count(self::$request_logs) . ' - Requests' . PHP_EOL;
        echo '____________________________________________________' . PHP_EOL;
        echo PHP_EOL;

        foreach (self::$request_logs as $entry)
        {
            echo $entry['request']->getMethod() . ' - ' . $entry['request']->getUri() . PHP_EOL;
            if ($entry['response'])
                echo $entry['response']->getStatusCode() . PHP_EOL;
            if ($entry['error'])
                echo $entry['error'] . PHP_EOL;

            echo PHP_EOL;
        }

        echo  PHP_EOL;
    }
}
