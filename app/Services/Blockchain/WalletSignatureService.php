<?php

namespace App\Services\Blockchain;

use InvalidArgumentException;
use kornrunner\Keccak;

class WalletSignatureService
{
    public function recoverAddress(string $message, string $signature): string
    {
        $signature = strtolower(preg_replace('/^0x/', '', $signature));

        if (! preg_match('/^[0-9a-f]{130}$/', $signature)) {
            throw new InvalidArgumentException('Signature Ethereum invalide.');
        }

        $messageHash = Keccak::hash(
            "\x19Ethereum Signed Message:\n".strlen($message).$message,
            256
        );

        $recoveryId = hexdec(substr($signature, 128, 2));
        if ($recoveryId >= 27) {
            $recoveryId -= 27;
        }

        if (! in_array($recoveryId, [0, 1], true)) {
            throw new InvalidArgumentException('Identifiant de récupération invalide.');
        }

        $publicKey = $this->recoverPublicKey(
            $messageHash,
            substr($signature, 0, 64),
            substr($signature, 64, 64),
            $recoveryId
        );
        $address = substr(Keccak::hash(hex2bin($publicKey), 256), -40);

        return '0x'.strtolower($address);
    }

    public function normalizeAddress(string $address): string
    {
        $address = strtolower($address);

        if (! preg_match('/^0x[0-9a-f]{40}$/', $address)) {
            throw new InvalidArgumentException('Adresse Ethereum invalide.');
        }

        return $address;
    }

    private function recoverPublicKey(string $hash, string $rHex, string $sHex, int $recoveryId): string
    {
        if (! extension_loaded('bcmath')) {
            throw new InvalidArgumentException('L’extension PHP bcmath est requise pour vérifier les signatures.');
        }

        $p = '115792089237316195423570985008687907853269984665640564039457584007908834671663';
        $n = '115792089237316195423570985008687907852837564279074904382605163141518161494337';
        $gx = '55066263022277343669578718895168534326250603453777594175500187360389116729240';
        $gy = '32670510020758816978083085130507043184471273380659243275938904335757337482424';

        $r = $this->hexToDecimal($rHex);
        $s = $this->hexToDecimal($sHex);
        if (bccomp($r, '0') <= 0 || bccomp($r, $n) >= 0 || bccomp($s, '0') <= 0 || bccomp($s, $n) >= 0) {
            throw new InvalidArgumentException('Composants de signature hors plage.');
        }

        $x = $r;
        $alpha = $this->mod(bcadd(bcmul(bcmul($x, $x), $x), '7'), $p);
        $exponent = bcdiv(bcadd($p, '1'), '4', 0);
        $y = bcpowmod($alpha, $exponent, $p);
        if ((int) bcmod($y, '2') !== $recoveryId) {
            $y = bcsub($p, $y);
        }

        $rPoint = [$x, $y];
        $e = $this->mod($this->hexToDecimal($hash), $n);
        $sR = $this->scalarMultiply($s, $rPoint, $p);
        $eG = $this->scalarMultiply($e, [$gx, $gy], $p);
        $minusEG = $eG ? [$eG[0], $this->mod(bcsub('0', $eG[1]), $p)] : null;
        $sum = $this->pointAdd($sR, $minusEG, $p);
        $publicKey = $this->scalarMultiply($this->inverse($r, $n), $sum, $p);

        if (! $publicKey) {
            throw new InvalidArgumentException('Impossible de récupérer la clé publique.');
        }

        return str_pad($this->decimalToHex($publicKey[0]), 64, '0', STR_PAD_LEFT)
            .str_pad($this->decimalToHex($publicKey[1]), 64, '0', STR_PAD_LEFT);
    }

    private function scalarMultiply(string $scalar, ?array $point, string $prime): ?array
    {
        $result = null;
        $addend = $point;

        while (bccomp($scalar, '0') > 0) {
            if (bcmod($scalar, '2') === '1') {
                $result = $this->pointAdd($result, $addend, $prime);
            }
            $addend = $this->pointAdd($addend, $addend, $prime);
            $scalar = bcdiv($scalar, '2', 0);
        }

        return $result;
    }

    private function pointAdd(?array $left, ?array $right, string $prime): ?array
    {
        if (! $left) {
            return $right;
        }
        if (! $right) {
            return $left;
        }

        [$x1, $y1] = $left;
        [$x2, $y2] = $right;

        if (bccomp($x1, $x2) === 0) {
            if ($this->mod(bcadd($y1, $y2), $prime) === '0') {
                return null;
            }
            $numerator = bcmul('3', bcmul($x1, $x1));
            $denominator = bcmul('2', $y1);
        } else {
            $numerator = bcsub($y2, $y1);
            $denominator = bcsub($x2, $x1);
        }

        $lambda = $this->mod(bcmul($numerator, $this->inverse($this->mod($denominator, $prime), $prime)), $prime);
        $x3 = $this->mod(bcsub(bcsub(bcmul($lambda, $lambda), $x1), $x2), $prime);
        $y3 = $this->mod(bcsub(bcmul($lambda, bcsub($x1, $x3)), $y1), $prime);

        return [$x3, $y3];
    }

    private function inverse(string $number, string $modulus): string
    {
        $oldR = $modulus;
        $r = $this->mod($number, $modulus);
        $oldT = '0';
        $t = '1';

        while (bccomp($r, '0') !== 0) {
            $quotient = bcdiv($oldR, $r, 0);
            [$oldR, $r] = [$r, bcsub($oldR, bcmul($quotient, $r))];
            [$oldT, $t] = [$t, bcsub($oldT, bcmul($quotient, $t))];
        }

        if (bccomp($oldR, '1') !== 0) {
            throw new InvalidArgumentException('Inverse elliptique inexistant.');
        }

        return $this->mod($oldT, $modulus);
    }

    private function mod(string $number, string $modulus): string
    {
        $result = bcmod($number, $modulus);

        return str_starts_with($result, '-') ? bcadd($result, $modulus) : $result;
    }

    private function hexToDecimal(string $hex): string
    {
        $decimal = '0';
        foreach (str_split(strtolower($hex)) as $digit) {
            $decimal = bcadd(bcmul($decimal, '16'), (string) hexdec($digit));
        }

        return $decimal;
    }

    private function decimalToHex(string $decimal): string
    {
        if ($decimal === '0') {
            return '0';
        }

        $hex = '';
        while (bccomp($decimal, '0') > 0) {
            $hex = dechex((int) bcmod($decimal, '16')).$hex;
            $decimal = bcdiv($decimal, '16', 0);
        }

        return $hex;
    }
}
