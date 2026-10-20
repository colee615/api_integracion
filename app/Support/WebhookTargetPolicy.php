<?php

namespace App\Support;

use InvalidArgumentException;

class WebhookTargetPolicy
{
    public function assertSafe(string $url): void
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower(rtrim((string) ($parts['host'] ?? ''), '.'));

        if ($scheme !== 'https' || $host === '' || isset($parts['user']) || isset($parts['pass'])) {
            throw new InvalidArgumentException('Webhook URL must use HTTPS and must not contain credentials.');
        }

        if ($host === 'localhost' || str_ends_with($host, '.localhost') || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
            throw new InvalidArgumentException('Webhook URL must resolve to a public host.');
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $addresses = [$host];
        } else {
            $addresses = gethostbynamel($host) ?: [];
            foreach (dns_get_record($host, DNS_AAAA) ?: [] as $record) {
                if (isset($record['ipv6'])) {
                    $addresses[] = $record['ipv6'];
                }
            }
        }

        if ($addresses === []) {
            throw new InvalidArgumentException('Webhook host could not be resolved to a public IP address.');
        }

        foreach (array_unique($addresses) as $address) {
            if (! filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new InvalidArgumentException('Webhook URL must resolve only to public IP addresses.');
            }
        }
    }
}
