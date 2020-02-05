<?php

namespace App\Services;

use Cloudflare\API\Auth\APIKey;
use Cloudflare\API\Endpoints\DNS;
use Cloudflare\API\Endpoints\Zones;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Class CloudflareService
 * @package App\Services
 */
class CloudFlareService
{
    private $adapter;

    private $dnsRecords = [];

    private $dnsRecordsLoaded = [];

    /**
     * @var DNS
     */
    private $dnsEndpoint;

    /**
     * @var Zones
     */
    private $zones;

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
    public function __construct(\Illuminate\Console\OutputStyle $output = null)
    {
        $email = config('cloudflare.email');
        $apiKey = config('cloudflare.apiKey');

        $key = new \Cloudflare\API\Auth\APIKey($email, $apiKey);
        $this->adapter = new \Cloudflare\API\Adapter\Guzzle($key);
        $this->dnsEndpoint = new \Cloudflare\API\Endpoints\DNS($this->adapter);
        $this->zones = new \Cloudflare\API\Endpoints\Zones($this->adapter);

        $this->output = $output;
    }

    /**
     * @param $domainName
     * @return bool
     * @throws \Cloudflare\API\Endpoints\EndpointException
     */
    public function isValidDomainName($domainName)
    {
        if (Str::endsWith($domainName, config('cloudflare.rootDomain'))) {
            return true;
        }

        if (!$this->isInValidRootDomainNames($domainName)) {
            return false;
        }

        $zoneId = $this->getZoneId($domainName);
        if (!$zoneId) {
            return false;
        }

        return true;
    }

    /**
     * @param $domainName
     * @return bool
     */
    private function isInValidRootDomainNames($domainName)
    {
        $rootDomain = $this->findRootDomain($domainName);
        return !!$rootDomain;
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
     * @param $domainName
     * @return string
     * @throws \Cloudflare\API\Endpoints\EndpointException
     */
    public function getZoneId($domainName)
    {
        $rootDomain = $this->findRootDomain($domainName);
        if (!$rootDomain) {
            return null;
        }

        return $this->zones->getZoneID($rootDomain);
    }

    private function findRootDomain($domainName)
    {
        $validDomains = config('cloudflare.validRootDomains') ?? [];

        foreach ($validDomains as $v) {
            if (Str::endsWith($domainName, $v)) {
                return $v;
            }
        }
        return null;
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
            $this->deleteDnsRecordRequest($v);
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
        if ($this->output) {
            $this->output->writeln('Writing ' . $type . ' record for domain ' . $domain . ' with value ' . $ip);
        }

        try {
            $this->dnsEndpoint->addRecord($this->getZoneId($domain), $type, $domain, $ip, 0, false);
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
    private function deleteDnsRecordRequest($record)
    {
        dd($record);
        if ($this->output) {
            $this->output->writeln('Deleting dns record ' . $record->id);
        }

        try {
            $this->dnsEndpoint->deleteRecord($record->zone, $record->id);
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
        dd($record);
        if ($this->output) {
            $this->output->writeln('Updating record ' . $record->name . ' with value ' . $record->content);
        }

        try {
            $this->dnsEndpoint->updateRecordDetails($record->zone, $record->id, [
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
        return $this->getDnsRecords($domain)
            ->where('name', '=', $domain)
            ->where('type', '=', $type);
    }

    /**
     * @return Collection
     * @throws \Cloudflare\API\Endpoints\EndpointException
     */
    private function getDnsRecords($domain)
    {
        $zoneId = $this->getZoneId($domain);
        if (!isset($this->dnsRecordsLoaded[$zoneId])) {
            $this->dnsRecordsLoaded[$zoneId] = true;
            $this->dnsRecords[$zoneId] = new Collection();
            foreach ($this->dnsEndpoint->listRecords($zoneId)->result as $record) {
                $this->dnsRecords[$zoneId]->add($record);
            }
        }
        return $this->dnsRecords[$zoneId];
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
