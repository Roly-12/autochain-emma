<?php

namespace Tests\Unit;

use App\Services\Blockchain\WalletSignatureService;
use PHPUnit\Framework\TestCase;

class WalletSignatureServiceTest extends TestCase
{
    public function test_it_recovers_public_eip_191_test_vector(): void
    {
        $signature = '0xe89fe57d906e3fa29381c074462c823ecef612485f83cc34ecb6bb511a3da7cf'
            .'6d82fc7ec21416f1542957e19e154ef00a9bcdd13510af2e911b6e3a6ea3fdd600';

        $address = (new WalletSignatureService())->recoverAddress('Login to app.xyz', $signature);

        $this->assertSame('0xcbaf22b5fa52647af668bb1e895bb8458028cde6', $address);
    }
}
