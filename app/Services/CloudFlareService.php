<?php

namespace App\Services;

use Cloudflare\API\Auth\APIKey;
use Cloudflare\API\Endpoints\DNS;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;

/**
 * Class CloudflareService
 * @package App\Services
 */
class CloudFlareService
{
    private $adapter;

    private $dnsRecords;

    private $dnsRecordsLoaded = false;

    /**
     * @var DNS
     */
    private $dnsEndpoint;

    /**
     * @var string
     */
    private $zone;

    /**
     * @var \Illuminate\Console\OutputStyle
     */
    private $output;

    /**
     * CloudFlareService constructor.
     */
    public function __construct(\Illuminate\Console\OutputStyle $output)
    {
        $email = config('cloudflare.CLOUDFLARE_EMAIL');
        $apiKey = config('cloudflare.CLOUDFLARE_API_KEY');

        $key = new \Cloudflare\API\Auth\APIKey($email, $apiKey);
        $this->adapter = new \Cloudflare\API\Adapter\Guzzle($key);

        $this->dnsRecords = new Collection();
        $this->dnsEndpoint = new \Cloudflare\API\Endpoints\DNS($this->adapter);
        $this->zone = config('cloudflare.CLOUDFLARE_ZONE');

        $this->output = $output;
    }

    /**
     * @param $type
     * @param $domain
     * @param $ip
     * @return bool
     * @throws CloudFlareException
     */
    public function setDnsRecord($type, $domain, $ip)
    {
        $this->updateOrCreateRecord($type, $domain, $ip);
        return true;
    }

    /**
     * @param $domain
     * @param $value
     * @throws CloudFlareException
     */
    public function setAcmeChallenge($domain, $value)
    {
        $this->setDnsRecord('TXT', '_acme-challenge.' . $domain, $value);
    }

    /**
     * @param $type
     * @param $domain
     * @param $value
     * @throws CloudFlareException
     */
    private function updateOrCreateRecord($type, $domain, $value)
    {
        // check if exist
        $existing = $this->getDnsRecordFromDomain($domain, $type);
        if ($existing->count() === 0) {
            $this->createDnsRecordRequest($type, $domain, $value);
            return;
        }

        $existingRecord = $existing->shift();

        // check if the value has changed.
        if ($existingRecord->content !== $value) {
            $existingRecord->content = $value;
            $this->updateDnsRecordRequest($type, $existingRecord);
        }

        foreach ($existing as $v) {
            $this->deleteDnsRecordRequest($v->id);
        }
    }

    /**
     * @param $type
     * @param $domain
     * @param $ip
     * @return bool
     * @throws CloudFlareException
     */
    private function createDnsRecordRequest($type, $domain, $ip)
    {
        $this->output->writeln('Writing ' . $type . ' record for domain ' . $domain . ' with value ' . $ip);
        try {
            $this->dnsEndpoint->addRecord($this->zone, $type, $domain, $ip, 0, false);
            return true;
        } catch (ClientException $e) {
            $this->handleErrors($e);
        } catch (GuzzleException $e) {
            die($e->getResponse()->getBody());
        }
        return false;
    }

    /**
     * @param $recordId
     * @return bool
     * @throws CloudFlareException
     */
    private function deleteDnsRecordRequest($recordId)
    {
        $this->output->writeln('Deleting dns record ' . $recordId);
        try {
            $this->dnsEndpoint->deleteRecord($this->zone, $recordId);
            return true;
        } catch (ClientException $e) {
            $this->handleErrors($e);
        } catch (GuzzleException $e) {
            die($e->getResponse()->getBody());
        }
        return false;
    }

    /**
     * @param $type
     * @param $recordId
     * @param $ip
     * @return bool
     * @throws CloudFlareException
     */
    private function updateDnsRecordRequest($type, $record)
    {
        $this->output->writeln('Updating record ' . $record->name . ' with value ' . $record->content);
        try {
            $this->dnsEndpoint->updateRecordDetails($this->zone, $record->id, [
                'type' => $record->type,
                'content' => $record->content,
                'name' => $record->name
            ]);
            return true;
        } catch (ClientException $e) {
            $this->handleErrors($e);
        } catch (GuzzleException $e) {
            die($e->getResponse()->getBody());
        }
        return false;
    }

    private function getDnsRecordFromDomain($domain, $type)
    {
        return $this->getDnsRecords()
            ->where('name', '=', $domain)
            ->where('type', '=', $type);
    }

    /**
     * @return Collection
     */
    private function getDnsRecords()
    {
        if (!$this->dnsRecordsLoaded) {
            $this->dnsRecordsLoaded = true;
            foreach ($this->dnsEndpoint->listRecords($this->zone)->result as $record) {
                $this->dnsRecords->add($record);
            }
        }
        return $this->dnsRecords;
    }

    /**
     * @param ClientException $error
     * @throws CloudFlareException
     */
    private function handleErrors(ClientException $error)
    {
        $content = json_decode($error->getResponse()->getBody(), true);
        if (isset($content['errors'])) {
            $error = $content['errors'][0];

            throw new CloudFlareException($error['message'], $error['code']);
        }
    }
}
