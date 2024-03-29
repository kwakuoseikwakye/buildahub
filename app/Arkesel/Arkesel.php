<?php

namespace App\Arkesel;

class Arkesel
{
    protected $apiKey;
    protected $senderId;
    protected $baseUrl = "https://sms.arkesel.com/sms/api";


    public function __construct(string $senderId, string $apiKey)
    {
        $this->senderId = $senderId;
        $this->apiKey = $apiKey;
    }

    public function send(string $recipient, string $msg)
    {
        $msg = urlencode($msg);
        $ch = curl_init("{$this->baseUrl}?action=send-sms&api_key={$this->apiKey}&to={$recipient}&from={$this->senderId}&sms={$msg}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            curl_close($ch);
            return curl_error($ch);
        }

        return $response;
    }
}