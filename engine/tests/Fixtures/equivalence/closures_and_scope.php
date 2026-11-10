<?php

declare(strict_types=1);

$prefix = 'P';

$arrow = fn (string $s): string => $prefix . '-arrow-' . $s;

$closure = function (string $s) use ($prefix): string {
    $local = 'closure';
    return $prefix . '-' . $local . '-' . $s;
};

echo $arrow('a') . "\n";
echo $closure('b') . "\n";

$makeCounter = function (): callable {
    $count = 0;
    return function (string $label) use (&$count): string {
        $count++;
        return $label . '=' . $count;
    };
};

$counter = $makeCounter();
echo $counter('hit') . ' ' . $counter('hit') . ' ' . $counter('hit') . "\n";

$numbers = [1, 2, 3, 4, 5];
$doubled = array_map(fn (int $n): int => $n * 2, $numbers);
$evens = array_filter($numbers, fn (int $n): bool => $n % 2 === 0);
echo implode(',', $doubled) . "\n";
echo implode(',', $evens) . "\n";

$reduce = array_reduce($numbers, fn (int $carry, int $n): int => $carry + $n, 0);
echo 'sum=' . $reduce . "\n";
