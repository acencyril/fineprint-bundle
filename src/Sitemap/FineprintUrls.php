<?php

declare(strict_types=1);

namespace Acencyril\FineprintBundle\Sitemap;

use Acencyril\FineprintBundle\Fineprint;

/**
 * The URLs of the enabled pages, for whatever builds the site's sitemap. Nothing more:
 * the bundle writes no sitemap itself.
 *
 * URLs are built on site.url rather than on the current request, so that a console
 * command produces the same list as a web request.
 */
final class FineprintUrls
{
    public function __construct(private readonly Fineprint $fineprint)
    {
    }

    /** @return list<array{loc: string, page: string, locale: string, alternates: array<string, string>}> */
    public function all(): array
    {
        $base = rtrim($this->fineprint->config()['site']['url'], '/');
        $out = [];
        foreach ($this->fineprint->enabledPages() as $page) {
            $alternates = [];
            foreach ($this->fineprint->locales() as $locale) {
                $alternates[$locale] = $base.$this->fineprint->path($page, $locale);
            }
            foreach ($alternates as $locale => $loc) {
                $out[] = ['loc' => $loc, 'page' => $page, 'locale' => $locale, 'alternates' => $alternates];
            }
        }

        return $out;
    }
}
