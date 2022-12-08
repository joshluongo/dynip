<?php

namespace App\Updater;

use App\Exceptions\InvalidAuthTokenException;
use App\Exceptions\InvalidDomainException;
use App\Exceptions\UpstreamErrorException;
use App\Objects\DNSUpdateObject;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;

class CloudflareUpdater {

    /**
     * Configuration Data.
     *
     * @var \stdClass
     */
    public $configuration;

    /**
     * CloudflareManager constructor.
     */
    public function __construct() {
        $this->configuration = json_decode(file_get_contents(__DIR__ . '/../../config/cloudflare.json'), false);
    }

    /**
     * Perform an update of DNS records.
     *
     * @param DNSUpdateObject $update
     * @throws InvalidDomainException
     * @throws \Throwable
     * @return string New IP Address
     */
    public function update(DNSUpdateObject $update) {
        // Find the body?
        if (!isset($this->configuration->{$update->domain})) {
            // Error!
            throw new InvalidDomainException();
        }

        // Get the body
        $body = $this->configuration->{$update->domain};

        // Validate
        $this->validate($update, $body);

        // Pass to CF
        return $this->submitUpdate($update, $body);
    }

    /**
     * Validate the update request.
     *
     * @param DNSUpdateObject $update
     *
     * @param \stdClass $body
     * @throws \Throwable
     */
    protected function validate(DNSUpdateObject $update, \stdClass $body) {
        throw_if(!hash_equals($body->url_key, $update->authKey), new InvalidAuthTokenException());
    }

    /**
     * Submit an update to Cloudflare.
     *
     * @param DNSUpdateObject $update
     * @param \stdClass $body
     * @throws GuzzleException
     * @throws UpstreamErrorException
     * @throws \Throwable
     * @return string New IP Address
     */
    protected function submitUpdate(DNSUpdateObject $update, \stdClass $body) {
        try {
            $client = new \GuzzleHttp\Client();
            $res = $client->request(
                'PUT',
                sprintf('https://api.cloudflare.com/client/v4/zones/%s/dns_records/%s', $body->record->domain_id, $body->record->zone_id), [
                    'headers' => [
                        "X-Auth-Email" => $body->auth->email,
                        "X-Auth-Key" => $body->auth->key,
                    ],
                    'json' => [
                        "type" => "A",
                        "name" => $body->record->name,
                        "content" => $update->ip ?? $_SERVER["REMOTE_ADDR"],
                        "ttl" => 120,
                        "proxied" => false
                    ]
                ]
            );

            // Throw on error.
            throw_if(($res->getStatusCode() != 200), new UpstreamErrorException("HTTP Error: " . $res->getStatusCode()));

            return $update->ip ?? $_SERVER["REMOTE_ADDR"];
        } catch (BadResponseException $e) {
            throw new UpstreamErrorException("HTTP Error: ".$e->getResponse()->getStatusCode() ?? 999);
        } catch (\Exception $e) {
            throw new UpstreamErrorException("Internal Server Error!");
        }
    }
}
