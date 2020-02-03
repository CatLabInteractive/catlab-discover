<?php


namespace App\Services;

use LEClient\LEClient;

/**
 * Class LetsEncrypt
 * @package App\Services
 */
class LetsEncrypt
{
    private $email;

    /**
     * @var LEClient
     */
    private $client;

    /**
     * LetsEncrypt constructor.
     */
    public function __construct()
    {
        $this->email = config('letsencrypt.email');

        $this->client = new LEClient(
            [ $this->email ],
            LEClient::LE_PRODUCTION,
            LEClient::LOG_STATUS,
            storage_path('keys/'),
            storage_path('__accounts/')
        );
    }

    public function generateSsl($domain)
    {
        $order = $this->client->getOrCreateOrder($domain, [ $domain ]);
        dd($order);
    }

}
