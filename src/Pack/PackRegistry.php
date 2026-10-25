<?php

declare(strict_types=1);

namespace Acencyril\FineprintBundle\Pack;

/** The packs found in the container, by name (built by RegisterPacksPass). */
final class PackRegistry
{
    /** @var array<string, PackInterface> */
    private array $packs = [];

    /** @param array<string, class-string<PackInterface>> $classes */
    public function __construct(private readonly array $classes)
    {
    }

    /** @return list<string> */
    public function names(): array
    {
        return array_keys($this->classes);
    }

    public function get(string $name): PackInterface
    {
        if (!isset($this->classes[$name])) {
            throw new \InvalidArgumentException(\sprintf('No Fineprint pack named "%s" (available: %s).', $name, implode(', ', $this->names())));
        }

        return $this->packs[$name] ??= new ($this->classes[$name])();
    }
}
