<?php

declare(strict_types=1);

namespace LaraCrypt\Engine\Tests\Unit;

use LaraCrypt\Engine\Support\BuildKey;
use PHPUnit\Framework\TestCase;

final class BuildKeyTest extends TestCase
{
    public function test_encode_decode_round_trips_arbitrary_bytes(): void
    {
        $key = new BuildKey('seed-abc');

        foreach (['', 'a', 'hello world', "binary\0\x01\xff bytes", str_repeat('x', 1000)] as $plain) {
            self::assertSame($plain, $key->decode($key->encode($plain)));
        }
    }

    public function test_encoding_is_deterministic_for_a_seed(): void
    {
        $a = new BuildKey('same-seed');
        $b = new BuildKey('same-seed');

        self::assertSame($a->encode('payload'), $b->encode('payload'));
        self::assertSame($a->hex(), $b->hex());
    }

    public function test_different_seeds_produce_different_keys(): void
    {
        $a = new BuildKey('seed-one');
        $b = new BuildKey('seed-two');

        self::assertNotSame($a->hex(), $b->hex());
        self::assertNotSame($a->encode('payload'), $b->encode('payload'));
    }

    public function test_decode_rejects_corrupt_payload(): void
    {
        $this->expectException(\RuntimeException::class);

        (new BuildKey('seed'))->decode('not valid base64 @@@');
    }
}
