<?php

namespace App\Services\Sms\Contracts;

interface SmsDriverInterface
{
    public function send(string $mobile, string $message, string $senderId): array;

    public function checkBalance(): array;

    public function testConnection(string $apiKey, string $senderId): array;
}
