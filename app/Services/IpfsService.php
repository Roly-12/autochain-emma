<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IpfsService
{
    public function isEnabled(): bool
    {
        return (bool) config('ipfs.enabled');
    }

    /**
     * Upload a file to IPFS (Kubo HTTP API). Returns CID or null if disabled/failed.
     */
    public function add(UploadedFile|string $file, ?string $filename = null): ?string
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        $name = $filename ?? ($file instanceof UploadedFile ? $file->getClientOriginalName() : basename((string) $path));

        try {
            $response = Http::timeout(30)
                ->attach('file', file_get_contents($path), $name)
                ->post(rtrim(config('ipfs.api_url'), '/').'/api/v0/add?pin=true');

            if (! $response->successful()) {
                Log::warning('IPFS upload failed', ['body' => $response->body()]);

                return null;
            }

            $json = $response->json();
            $cid = $json['Hash'] ?? $json['Cid'] ?? null;
            if (is_array($cid)) {
                $cid = $cid['/'] ?? null;
            }

            if (! $this->isValidCid($cid) || ! $this->pin($cid)) {
                Log::warning('IPFS returned an invalid or unpinned CID', ['cid' => $cid]);

                return null;
            }

            return $cid;
        } catch (ConnectionException $e) {
            Log::warning('IPFS unreachable: '.$e->getMessage());

            return null;
        }
    }

    public function pin(string $cid): bool
    {
        if (! $this->isValidCid($cid)) {
            return false;
        }

        try {
            return Http::timeout(30)
                ->post(rtrim(config('ipfs.api_url'), '/').'/api/v0/pin/add?arg='.urlencode($cid))
                ->successful();
        } catch (ConnectionException) {
            return false;
        }
    }

    public function isValidCid(?string $cid): bool
    {
        if (! $cid) {
            return false;
        }

        return (bool) preg_match('/^(Qm[1-9A-HJ-NP-Za-km-z]{44}|b[a-z2-7]{20,})$/', $cid);
    }

    public function gatewayUrl(?string $cid): ?string
    {
        if (! $cid) {
            return null;
        }

        return rtrim(config('ipfs.gateway_url'), '/').'/'.$cid;
    }
}
