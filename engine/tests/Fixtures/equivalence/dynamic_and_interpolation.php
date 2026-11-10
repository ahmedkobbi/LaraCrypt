<?php

declare(strict_types=1);

// Dynamic string resolution: encoding literals must preserve their runtime value
// so these keep working (compact/callables/class strings resolve by value).

$name = 'Ada';
$role = 'engineer';

// Interpolated strings are a different node type and are left intact.
echo "User $name is a $role\n";
echo "Sum: {${'name'}}\n";

$heredoc = <<<TXT
    multi
    line $role
    heredoc
    TXT;
echo $heredoc . "\n";

$nowdoc = <<<'TXT'
    raw $notvar literal
    TXT;
echo $nowdoc . "\n";

// Variable function call via a string literal value.
$fn = 'strtoupper';
echo $fn('dynamic-call') . "\n";

// call_user_func with a string callable.
echo call_user_func('strrev', 'abcdef') . "\n";

// compact() relies on the literal 'name'/'role' values matching variable names.
$packed = compact('name', 'role');
echo $packed['name'] . '/' . $packed['role'] . "\n";

// class_exists + method call via string value.
echo (class_exists('DateTime') ? 'has-datetime' : 'no') . "\n";

// Array of string keys built and read dynamically.
$map = [];
foreach (['red', 'green', 'blue'] as $color) {
    $map[$color] = 'color-' . $color;
}
echo $map['green'] . "\n";
