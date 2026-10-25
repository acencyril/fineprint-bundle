<?php

declare(strict_types=1);

namespace Acencyril\FineprintBundle\Pack;

/**
 * Helpers for packs. Values set through %env()% are not known at build time: they reach
 * validate() as AbstractPack::DYNAMIC, which filled() accepts and format checks skip.
 */
abstract class AbstractPack implements PackInterface
{
    public const DYNAMIC = "\0fineprint-env";

    public function translationDomain(): string
    {
        return 'fineprint_'.$this->name();
    }

    public function requiredFields(array $config): array
    {
        return [];
    }

    public function validate(array $config): array
    {
        return [];
    }

    public static function get(array $config, string $path): mixed
    {
        $value = $config;
        foreach (explode('.', $path) as $key) {
            if (!\is_array($value) || !\array_key_exists($key, $value)) {
                return null;
            }
            $value = $value[$key];
        }

        return $value;
    }

    public static function filled(mixed $value): bool
    {
        return null !== $value && (!\is_string($value) || '' !== trim($value)) && [] !== $value;
    }

    public static function dynamic(mixed $value): bool
    {
        return self::DYNAMIC === $value;
    }

    /** @param list<string> $activities */
    public static function has(array $config, string ...$activities): bool
    {
        return [] === array_diff($activities, $config['activities']);
    }

    /** A clause of this pack, with $packs set to it. */
    protected function clause(string $id, string $page, mixed ...$options): Clause
    {
        return new Clause($id, $page, [$this->name()], ...$options);
    }
}
