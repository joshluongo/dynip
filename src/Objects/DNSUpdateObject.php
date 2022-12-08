<?php

namespace App\Objects;

class DNSUpdateObject {

    /**
     * Auth Key.
     *
     * @var string
     */
    public $authKey;

    /**
     * Domain
     *
     * @var string
     */
    public $domain;

    /**
     * IP to use.
     *
     * @var string|null
     */
    public $ip;

    /**
     * Fill data using magic variables.
     *
     * @return void
     */
    public function fill() {
        // Fill data.
        $this->domain = $_REQUEST["domain"] ?? ($_SERVER["PHP_AUTH_USER"] ?? "");
        $this->authKey = $_REQUEST["key"] ?? ($_SERVER["PHP_AUTH_PW"] ?? "");
        $this->ip = $_REQUEST["ip"] ?? null;
    }

}