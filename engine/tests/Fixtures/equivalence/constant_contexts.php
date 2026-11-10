<?php

declare(strict_types=1);

// Every string here lives in a compile-time-constant context and MUST NOT be
// encoded (doing so would be a fatal error). This fixture guards that rule.

const GLOBAL_CONST = 'global-const-value';

#[\Attribute]
final class Label
{
    public function __construct(public string $text = 'default-label')
    {
    }
}

enum Status: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    public function describe(): string
    {
        // This string IS in a runtime context and may be encoded.
        return 'status:' . $this->value;
    }
}

#[Label('on-class')]
class Config
{
    public const VERSION = 'v2.5.0';

    public const TAGS = ['a-tag', 'b-tag'];

    public string $env = 'production';

    public static string $region = 'eu-west';

    public function summary(string $prefix = 'cfg'): string
    {
        static $cached = 'static-init';

        return $prefix . ':' . self::VERSION . ':' . $this->env . ':' . self::$region
            . ':' . implode(',', self::TAGS) . ':' . $cached;
    }
}

echo GLOBAL_CONST . "\n";
echo Status::Active->value . '|' . Status::Inactive->describe() . "\n";
echo (new Config())->summary() . "\n";
echo (new Config())->summary('custom') . "\n";
echo (new Label())->text . "\n";
