<?php

use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

function sendNotification($data)
{
    $client = new Client(); //GuzzleHttp\Client
    $url = "https://onesignal.com/api/v1/notifications";

    $headers = [
        'Content-Type' => 'application/json; charset=utf-8',
        'Authorization' => 'Basic MmUwYTJhYTEtZGIwNi00MDgwLWIwYWQtZjdlNGY1ODRkNGE5',
    ];

    $response = $client->request('POST', $url, [
        'headers' => $headers,
        'body' => json_encode($data),
    ]);

    $statusCode = $response->getStatusCode();
    $content = $response->getBody();

    return $content;
}

$data = [
    'app_id' => '866c8f05-7c61-4161-85b0-c447c019d138',
    'include_player_ids' => ['6392d91a-b206-4b7b-a620-cd68e32c3a76'],
    'email_subject' => 'Welcome to Cat Facts!',
    'email_body' => '<html><head>Welcome to Cat Facts</head><body><h1>Welcome to Cat Facts<h1><h4>Learn more about everyone\'s favorite furry companions!</h4><hr/><p>Hi Nick,</p><p>Thanks for subscribing to Cat Facts! We can\'t wait to surprise you with funny details about your favorite animal.</p><h5>Today\'s Cat Fact (March 27)</h5><p>In tigers and tabbies, the middle of the tongue is covered in backward-pointing spines, used for breaking off and gripping meat.</p><a href=\'https://catfac.ts/welcome\'>Show me more Cat Facts</a><hr/><p><small>(c) 2018 Cat Facts, inc</small></p><p><small><a href=\'[unsubscribe_url]\'>Unsubscribe</a></small></p></body></html>'
];
