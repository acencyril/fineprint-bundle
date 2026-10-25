<?php

declare(strict_types=1);

namespace Acencyril\FineprintBundle\Twig;

use Acencyril\FineprintBundle\Fineprint;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * fineprint_links()  the enabled pages, for a footer: [{page, label, url}, …]
 * fineprint_url(p)   the URL of one page, or null when it is disabled
 * |fineprint_text    picks the current locale's version of a configured text
 * |fineprint_paragraphs  splits a configured text on blank lines
 */
final class FineprintTwigExtension extends AbstractExtension
{
    public function __construct(
        private readonly Fineprint $fineprint,
        private readonly UrlGeneratorInterface $urls,
        private readonly TranslatorInterface $translator,
        private readonly RequestStack $requests,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('fineprint_links', $this->links(...)),
            new TwigFunction('fineprint_url', $this->url(...)),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('fineprint_paragraphs', static fn (?string $v) => null === $v ? [] : array_values(array_filter(array_map('trim', preg_split('/\R\s*\R/', $v)), static fn (string $p) => '' !== $p))),
            new TwigFilter('fineprint_text', fn (mixed $v, ?string $locale = null) => $this->fineprint->text($v, $locale ?? $this->locale())),
        ];
    }

    /** @return list<array{page: string, label: string, url: string}> */
    public function links(?string $locale = null): array
    {
        $locale = $this->fineprint->resolveLocale($locale ?? $this->locale());
        $links = [];
        foreach ($this->fineprint->enabledPages() as $page) {
            $links[] = [
                'page' => $page,
                'label' => $this->translator->trans($page.'.link', [], 'fineprint', $locale),
                'url' => $this->urls->generate($this->fineprint->routeName($page), ['_locale' => $locale]),
            ];
        }

        return $links;
    }

    public function url(string $page, ?string $locale = null, bool $absolute = false): ?string
    {
        if (!$this->fineprint->isEnabled($page)) {
            return null;
        }

        return $this->urls->generate(
            $this->fineprint->routeName($page),
            ['_locale' => $this->fineprint->resolveLocale($locale ?? $this->locale())],
            $absolute ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH,
        );
    }

    private function locale(): string
    {
        return $this->fineprint->resolveLocale($this->requests->getCurrentRequest()?->getLocale());
    }
}
