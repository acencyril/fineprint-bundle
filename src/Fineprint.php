<?php

declare(strict_types=1);

namespace Acencyril\FineprintBundle;

/**
 * The processed configuration, and the few questions every part of the bundle asks of it.
 */
final class Fineprint
{
    public const PAGES = ['legal_notice', 'terms', 'privacy', 'cookies'];

    public function __construct(private readonly array $config)
    {
    }

    /**
     * Derived values, computed once at build time: the processors as the pages list them
     * (the host first, when it is one) and the section overrides in their long form.
     */
    public static function prepare(array $config): array
    {
        $list = [];
        $host = $config['host'];
        if ($config['privacy']['host_is_processor'] && null !== $host['name'] && '' !== $host['name']) {
            $list['host'] = ['name' => $host['name'], 'role' => 'hosting', 'country' => null, 'outside_eu' => false, 'description' => null, 'safeguards' => null];
        }
        $config['privacy']['processor_list'] = $list + $config['privacy']['processors'];

        foreach (self::PAGES as $page) {
            foreach ($config['pages'][$page]['sections']['override'] as $id => $o) {
                if (\is_string($o)) {
                    $o = str_ends_with($o, '.twig') ? ['template' => $o] : ['body' => $o];
                }
                $config['pages'][$page]['sections']['override'][$id] = $o + ['body' => null, 'template' => null, 'title' => null, 'domain' => 'messages'];
            }
        }

        return $config;
    }

    public function config(): array
    {
        return $this->config;
    }

    public function jurisdiction(): string
    {
        return $this->config['jurisdiction'];
    }

    /** @return list<string> */
    public function locales(): array
    {
        return array_values($this->config['locales']);
    }

    /** @return list<string> the enabled pages, in display order */
    public function enabledPages(): array
    {
        return array_values(array_filter(self::PAGES, fn (string $p) => $this->isEnabled($p)));
    }

    public function isEnabled(string $page): bool
    {
        return (bool) ($this->config['pages'][$page]['enabled'] ?? false);
    }

    public function routeName(string $page): string
    {
        return 'fineprint_'.$page;
    }

    public function path(string $page, string $locale): string
    {
        return '/'.$locale.'/'.trim($this->config['pages'][$page]['path'][$locale], '/');
    }

    /** The configured locale closest to $locale ("fr_BE" gives "fr"), else the first one. */
    public function resolveLocale(?string $locale): string
    {
        $locales = $this->locales();
        while (null !== $locale && '' !== $locale) {
            if (\in_array($locale, $locales, true)) {
                return $locale;
            }
            $locale = false !== strpos($locale, '_') ? strstr($locale, '_', true) : null;
        }

        return $locales[0];
    }

    /**
     * Picks a configured text for a locale: a plain string serves every locale, a map falls
     * back to the bundle's first locale, then to its first entry.
     */
    public function text(mixed $value, string $locale): ?string
    {
        if (null === $value || \is_string($value)) {
            return $value;
        }
        $locale = $this->resolveTextLocale($value, $locale);

        return null === $locale ? null : $value[$locale];
    }

    private function resolveTextLocale(array $value, string $locale): ?string
    {
        foreach ([$locale, strstr($locale, '_', true) ?: $locale, $this->locales()[0]] as $l) {
            if (isset($value[$l])) {
                return $l;
            }
        }
        $first = array_key_first($value);

        return null === $first ? null : (string) $first;
    }
}
