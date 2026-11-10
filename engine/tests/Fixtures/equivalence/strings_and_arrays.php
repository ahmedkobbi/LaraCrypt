<?php

declare(strict_types=1);

$greeting = 'Hello';
$target = 'World';
echo $greeting . ', ' . $target . "!\n";

$data = [
    'first' => 'alpha',
    'second' => 'beta',
    'nested' => ['deep' => 'value-here'],
];

echo $data['first'] . '|' . $data['second'] . '|' . $data['nested']['deep'] . "\n";

foreach (['one', 'two', 'three'] as $word) {
    echo strtoupper($word) . ' ';
}
echo "\n";

$concat = '';
for ($i = 0; $i < 3; $i++) {
    $concat .= 'x' . $i;
}
echo $concat . "\n";

echo str_replace('fox', 'cat', 'the quick brown fox') . "\n";
echo json_encode(['msg' => 'serialize-me', 'ok' => true]) . "\n";
