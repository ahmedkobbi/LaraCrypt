<?php

declare(strict_types=1);

interface Shape
{
    public function name(): string;

    public function area(): float;
}

trait Describable
{
    public function describe(): string
    {
        return $this->name() . ' area=' . number_format($this->area(), 2);
    }
}

abstract class BaseShape implements Shape
{
    use Describable;

    public function __construct(protected string $label = 'shape')
    {
    }
}

final class Rectangle extends BaseShape
{
    public function __construct(private float $w, private float $h)
    {
        parent::__construct('rectangle');
    }

    public function name(): string
    {
        return $this->label;
    }

    public function area(): float
    {
        return $this->w * $this->h;
    }
}

function classify(string $kind): string
{
    return match ($kind) {
        'r', 'rect' => 'rectangle-kind',
        'c' => 'circle-kind',
        default => 'unknown-kind',
    };
}

$shapes = [new Rectangle(2.0, 3.0), new Rectangle(4.0, 5.0)];
foreach ($shapes as $shape) {
    echo $shape->describe() . "\n";
}

foreach (['r', 'c', 'z'] as $k) {
    echo classify($k) . ' ';
}
echo "\n";

$level = 'warning';
switch ($level) {
    case 'error':
        echo "is-error\n";
        break;
    case 'warning':
        echo "is-warning\n";
        break;
    default:
        echo "is-other\n";
}

try {
    throw new \RuntimeException('boom-message');
} catch (\RuntimeException $e) {
    echo 'caught:' . $e->getMessage() . "\n";
} finally {
    echo "finally-ran\n";
}
