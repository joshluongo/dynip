<?php
ini_set('display_errors', 0);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Objects\DNSUpdateObject;
use App\Updater\CloudflareUpdater;

// Text Header
header('Content-Type: text/plain');

// Get the path data.
$uri = $_SERVER["REQUEST_URI"];

// We only support "/update"
if (!str_starts_with($uri, "/update")) {
    echo "not found";
    http_response_code(404);
    exit(1);
}

try {
    // CF Manager.
    $man = new CloudflareUpdater();

    // Update Object.
    $updateObj = new DNSUpdateObject();
    $updateObj->fill();

    // Attempt the update.
    $ip = $man->update($updateObj);

    echo "good $ip";
    exit(0);
} catch (\App\Exceptions\InvalidAuthTokenException $e) {
    echo "badauth";
    http_response_code(401);
    exit(1);
} catch (\App\Exceptions\InvalidDomainException $e) {
    echo "nohost";
    http_response_code(400);
    exit(1);
} catch (\Exception $e) {
    echo "911";
    http_response_code(500);
    exit(1);
}