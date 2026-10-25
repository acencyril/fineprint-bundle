<?php

declare(strict_types=1);

namespace Acencyril\FineprintBundle\Assembly;

use Acencyril\FineprintBundle\Fineprint;
use Acencyril\FineprintBundle\Pack\Clause;
use Acencyril\FineprintBundle\Pack\PackInterface;
use Acencyril\FineprintBundle\Pack\PackRegistry;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Builds a page from the pack's clauses: keeps those that fit the site's activities and
 * configuration, then applies the page's sections (remove, override, add).
 */
final class Assembler
{
    public function __construct(
        private readonly Fineprint $fineprint,
        private readonly PackRegistry $packs,
        private readonly TranslatorInterface $translator,
        private readonly UrlGeneratorInterface $urls,
    ) {
    }

    public function pack(): PackInterface
    {
        return $this->packs->get($this->fineprint->jurisdiction());
    }

    public function domain(): string
    {
        return $this->pack()->translationDomain();
    }

    /**
     * The plan of a page, before any text: each entry is a clause, possibly overridden,
     * or a section the site added.
     *
     * @return list<array{id: string, clause: ?Clause, override: ?array, added: ?array}>
     */
    public static function plan(PackInterface $pack, array $config, string $page): array
    {
        $sections = $config['pages'][$page]['sections'];
        $plan = [];
        foreach ($pack->clauses() as $clause) {
            if ($clause->page !== $page || \in_array($clause->id, $sections['remove'], true) || !$clause->appliesTo($config['activities'], $config)) {
                continue;
            }
            $plan[] = ['id' => $clause->id, 'clause' => $clause, 'override' => $sections['override'][$clause->id] ?? null, 'added' => null];
        }

        foreach ($sections['add'] as $added) {
            $entry = ['id' => $added['id'], 'clause' => null, 'override' => null, 'added' => $added];
            $ids = array_column($plan, 'id');
            [$where, $anchor] = explode(':', $added['position'].':', 3);
            $at = match ($where) {
                'start' => 0,
                'before' => array_search($anchor, $ids, true),
                'after' => false === ($i = array_search($anchor, $ids, true)) ? false : $i + 1,
                default => \count($plan),
            };
            // An anchor the activities filtered out: the section goes at the end.
            array_splice($plan, false === $at ? \count($plan) : $at, 0, [$entry]);
        }

        return $plan;
    }

    /**
     * The page, ready for its template.
     *
     * @return list<array{id: string, title: ?string, paragraphs: list<string>, template: ?string, link: ?array, domain: string}>
     */
    public function sections(string $page, string $locale): array
    {
        $config = $this->fineprint->config();
        $pack = $this->pack();
        $domain = $pack->translationDomain();
        $params = $this->params($locale);
        $out = [];

        foreach (self::plan($pack, $config, $page) as $entry) {
            $section = ['id' => $entry['id'], 'title' => null, 'paragraphs' => [], 'template' => null, 'link' => null, 'domain' => $domain];
            if (null !== $added = $entry['added']) {
                $section['title'] = $this->fineprint->text($added['title'], $locale);
                $section['template'] = $added['template'];
                $section['paragraphs'] = self::paragraphs($this->fineprint->text($added['body'], $locale));
                $out[] = $section;
                continue;
            }

            /** @var Clause $clause */
            $clause = $entry['clause'];
            $override = $entry['override'];
            $section['title'] = null !== ($override['title'] ?? null)
                ? $this->fineprint->text($override['title'], $locale)
                : (null === $clause->title ? null : $this->translator->trans($clause->title, $params, $domain, $locale));

            if (null !== $override) {
                $section['template'] = $override['template'];
                if (null !== $override['body']) {
                    $section['paragraphs'] = self::paragraphs($this->translator->trans($override['body'], $params, $override['domain'], $locale));
                }
            } else {
                $section['template'] = $clause->template;
                if (null !== $clause->body) {
                    $section['paragraphs'] = self::paragraphs($this->translator->trans($clause->body, $params, $domain, $locale));
                }
                if (null !== $clause->link) {
                    $section['link'] = [
                        'label' => $this->translator->trans('page.'.$clause->link.'.link_inline', [], $domain, $locale),
                        'url' => $this->fineprint->isEnabled($clause->link)
                            ? $this->urls->generate($this->fineprint->routeName($clause->link), ['_locale' => $locale])
                            : null,
                    ];
                }
            }
            $out[] = $section;
        }

        return $out;
    }

    /** The values every clause text may use. @return array<string, string> */
    public function params(string $locale): array
    {
        $c = $this->fineprint->config();
        $t = fn (mixed $v) => (string) $this->fineprint->text($v, $locale);

        return [
            '%site%' => $c['site']['name'],
            '%url%' => $c['site']['url'],
            '%seller%' => $c['publisher']['legal_name'],
            '%email%' => $c['publisher']['email'],
            '%country%' => (string) $c['publisher']['country'],
            '%law%' => $c['governing_law'],
            '%court%' => (string) $c['terms']['court'],
            '%provider%' => (string) $c['terms']['payment_provider'],
            '%ranking%' => $t($c['terms']['marketplace_ranking']),
            '%cancellation%' => $t($c['terms']['subscription_cancellation']),
            '%controller%' => $c['privacy']['controller'] ?: $c['publisher']['legal_name'],
            '%contact%' => $c['privacy']['contact_email'] ?: $c['publisher']['email'],
            '%authority%' => (string) $c['privacy']['authority']['name'],
            '%authority_url%' => (string) $c['privacy']['authority']['url'],
        ];
    }

    /** @return list<string> */
    public static function paragraphs(?string $text): array
    {
        if (null === $text) {
            return [];
        }

        return array_values(array_filter(array_map('trim', preg_split('/\R\s*\R/', $text)), static fn (string $p) => '' !== $p));
    }
}
