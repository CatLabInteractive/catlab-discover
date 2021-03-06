<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Services\CloudFlareException;
use App\Services\CloudFlareService;
use App\Services\LetsEncrypt;
use Illuminate\Console\Command;

class UpdateDevice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'devices:update {deviceId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update a devices (domain name && ssl)';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws CloudFlareException
     */
    public function handle()
    {
        $deviceId = $this->argument('deviceId');

        /** @var Device $device */
        $device = Device::findOrFail($deviceId);

        $cloudFlare = new CloudFlareService($this->output);

        // does this device have a domain yet?
        if (!$device->domain) {
            $domainName = $device->generateDomainName();

            $device->domain = $domainName;
            $device->save();
        }

        $this->output->writeln('Updating records for: <info>' . $device->domain . '</info>');
        $result = $cloudFlare->setDnsRecord('A', $device->domain, $device->ip);

        if ($device->needsRefreshCertificate()) {
            $this->output->writeln('Updating certificate');
            $this->updateSslCertificate($device, $cloudFlare);
        } else {
            $this->output->writeln('Not updating certificate, valid until: <info>' . $device->getLastCertificate()->expires . '</info>');
        }
    }

    /**
     * @param Device $device
     * @param CloudFlareService $cloudFlare
     * @throws CloudFlareException
     */
    protected function updateSslCertificate(Device $device, CloudFlareService $cloudFlare)
    {
        $letsEncrypt = new LetsEncrypt($this->output);
        $letsEncrypt->generateSsl($device, $cloudFlare);
    }
}
