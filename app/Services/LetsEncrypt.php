<?php


namespace App\Services;

use App\Models\Device;
use App\Models\SslCertificate;
use Carbon\Carbon;
use LEClient\LEClient;
use LEClient\LEOrder;

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
            LEClient::LOG_OFF,
            storage_path('letsencrypt')
        );
    }

    /**
     * @param Device $device
     * @param CloudFlareService $dnsService
     * @throws CloudFlareException
     */
    public function generateSsl(Device $device, CloudFlareService $dnsService)
    {
        $domain = $device->domain;
        $order = $this->client->getOrCreateOrder($domain, [ $domain ]);

        //dd($order);
        if(!$order->allAuthorizationsValid()) {
            $pending = $order->getPendingAuthorizations(LEOrder::CHALLENGE_TYPE_DNS);
            if (!empty($pending)) {
                foreach ($pending as $challenge) {
                    $dnsService->setAcmeChallenge($challenge['identifier'], $challenge['DNSDigest']);
                    $order->verifyPendingOrderAuthorization($challenge['identifier'], LEOrder::CHALLENGE_TYPE_DNS);
                }
            }
        }

        if($order->allAuthorizationsValid()) {
            if(!$order->isFinalized()) {
                $order->finalizeOrder();
            }

            if($order->isFinalized()) {
                if ($order->getCertificate()) {
                    $this->setCertificate($device, $order);
                    return true;
                }
            }
        }

        return false;
    }

    protected function setCertificate(Device $device, LEOrder $order)
    {
        $public = file_get_contents(storage_path('letsencrypt/public.pem'));
        $private = file_get_contents(storage_path('letsencrypt/private.pem'));
        $certificateContent = file_get_contents(storage_path('letsencrypt/certificate.crt'));
        $orderUrl = file_get_contents(storage_path('letsencrypt/order'));

        $certificate = SslCertificate::getOrCreate($orderUrl);
        $certificate->device()->associate($device->id);

        $certificate->public_key = $public;
        $certificate->private_key = $private;
        $certificate->certificate = $certificateContent;
        $certificate->status = $order->status;
        $certificate->expires = Carbon::parse($order->expires);

        $certificate->save();

    }

}
