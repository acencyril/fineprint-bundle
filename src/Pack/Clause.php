<?php

declare(strict_types=1);

namespace Acencyril\FineprintBundle\Pack;

/**
 * One section of a page, and the conditions under which it is assembled.
 *
 * A clause is shown when the site has every activity in $activities, at least one of
 * $anyOf (when set), none of $excludes, and every condition in $when holds. A condition
 * is a configuration path that must be truthy ("terms.court"), the same path prefixed
 * with "!" that must be falsy, or "page:<id>" for an enabled page.
 *
 * Its text is a translation key ($body, in the pack's domain, blank lines start a new
 * paragraph) or a Twig template; $title is a translation key too, or null for a clause
 * that continues the previous one. $link names a page whose link ends the last
 * paragraph.
 */
final class Clause
{
    /**
     * @param list<string> $activities all of them are required
     * @param list<string> $anyOf      at least one of them is required, when not empty
     * @param list<string> $excludes   none of them may be present
     * @param list<string> $when       configuration conditions
     * @param list<string> $packs      the packs the clause belongs to
     */
    public function __construct(
        public readonly string $id,
        public readonly string $page,
        public readonly array $packs,
        public readonly ?string $title = null,
        public readonly ?string $body = null,
        public readonly ?string $template = null,
        public readonly array $activities = [],
        public readonly array $anyOf = [],
        public readonly array $excludes = [],
        public readonly array $when = [],
        public readonly ?string $link = null,
    ) {
        if ((null === $body) === (null === $template)) {
            throw new \LogicException(\sprintf('Clause "%s" needs exactly one of a body or a template.', $id));
        }
    }

    /** @param list<string> $activities the site's */
    public function appliesTo(array $activities, array $config): bool
    {
        if ([] !== array_diff($this->activities, $activities)) {
            return false;
        }
        if ([] !== $this->anyOf && [] === array_intersect($this->anyOf, $activities)) {
            return false;
        }
        if ([] !== array_intersect($this->excludes, $activities)) {
            return false;
        }
        foreach ($this->when as $condition) {
            if (!self::holds($condition, $config)) {
                return false;
            }
        }

        return true;
    }

    public static function holds(string $condition, array $config): bool
    {
        if (str_starts_with($condition, 'page:')) {
            return (bool) ($config['pages'][substr($condition, 5)]['enabled'] ?? false);
        }
        $negate = str_starts_with($condition, '!');
        $value = AbstractPack::get($config, ltrim($condition, '!'));
        $truthy = null !== $value && false !== $value && '' !== $value && [] !== $value;

        return $negate ? !$truthy : $truthy;
    }
}
