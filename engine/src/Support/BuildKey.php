<?php

declare(strict_types=1);

namespace LaraCrypt\Engine\Support;

/**
 * Per-build symmetric key material derived deterministically from the build seed.
 *
 * NOTE ON STRENGTH: the source-layer string codec (XOR keystream + base64) is an
 * *obfuscation* primitive, not a confidentiality guarantee — the key lives in the
 * emitted runtime. Real cryptographic protection is Layer 2 (encrypted bytecode)
 * enforced by the native loader, where the key never touches readable disk.
 * This class exists so the source layer is reproducible and polymorphic per seed.
 */
final class BuildKey
{
    /** @var non-empty-string */
    public readonly string $bytes;

    /**
     * @param non-empty-string $seed
     */
    public function __construct(string $seed)
    {
        // 32 bytes of key material, bound to the seed.
        $this->bytes = hash('sha256', 'laracrypt/v1/' . $seed, true);
    }

    /** Lowercase hex form, safe to embed in generated PHP. */
    public function hex(): string
    {
        return bin2hex($this->bytes);
    }

    /**
     * Encode a plaintext string into the on-disk representation: base64(XOR keystream).
     * Pure function of ($plaintext, key) — deterministic and exactly reversible.
     */
    public function encode(string $plaintext): string
    {
        return base64_encode(self::xor($plaintext, $this->bytes));
    }

    /**
     * Reverse of {@see encode()}. Kept here so tests can round-trip without the
     * generated runtime, and so both sides share one implementation (no drift).
     */
    public function decode(string $payload): string
    {
        $raw = base64_decode($payload, true);

        if ($raw === false) {
            throw new \RuntimeException('Corrupt LaraCrypt string payload.');
        }

        return self::xor($raw, $this->bytes);
    }

    /**
     * @param non-empty-string $key
     */
    private static function xor(string $data, string $key): string
    {
        $out = '';
        $klen = strlen($key);

        for ($i = 0, $len = strlen($data); $i < $len; $i++) {
            $out .= $data[$i] ^ $key[$i % $klen];
        }

        return $out;
    }
}
