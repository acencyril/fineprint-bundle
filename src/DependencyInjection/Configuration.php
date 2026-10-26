<?php

declare(strict_types=1);

namespace Acencyril\FineprintBundle\DependencyInjection;

use Acencyril\FineprintBundle\Fineprint;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Builder\VariableNodeDefinition;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Everything the pages say about the publisher comes from here, never from the bundle.
 *
 * This tree checks the shape of the configuration and what every jurisdiction needs.
 * What a given law requires (a SIRET, a mediator, the host's phone) is checked by the
 * jurisdiction's pack once the container is built (RegisterPacksPass), because packs
 * come from other bundles too.
 *
 * Format checks let "" through and the emptiness of those fields is checked at the root:
 * an %env()% value is checked against an empty dummy at build time, and Symfony refuses
 * env values on a node that both validates and cannot be empty.
 */
final class Configuration implements ConfigurationInterface
{
    public const ACTIVITIES = ['b2b', 'b2c', 'digital', 'subscription', 'physical_goods', 'marketplace', 'services'];
    public const LEGAL_BASES = ['consent', 'contract', 'legal_obligation', 'vital_interests', 'public_task', 'legitimate_interests'];
    public const PROCESSOR_ROLES = ['hosting', 'email', 'payment', 'ai', 'analytics', 'other'];
    public const COOKIE_TYPES = ['essential', 'preferences', 'analytics', 'marketing'];

    private const DEFAULT_PATHS = [
        'legal_notice' => ['fr' => 'mentions-legales', 'en' => 'legal-notice'],
        'terms' => ['fr' => 'cgv', 'en' => 'terms-of-sale'],
        'privacy' => ['fr' => 'confidentialite', 'en' => 'privacy'],
        'cookies' => ['fr' => 'cookies', 'en' => 'cookies'],
    ];

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $tree = new TreeBuilder('fineprint');
        $root = $tree->getRootNode();

        $pages = (new TreeBuilder('pages'))->getRootNode()->addDefaultsIfNotSet();
        $pageChildren = $pages->children();
        foreach (Fineprint::PAGES as $page) {
            $pageChildren->append($this->page($page));
        }
        $pageChildren->end();

        $root
            ->children()
                ->scalarNode('jurisdiction')->isRequired()->cannotBeEmpty()
                    ->info('The pack whose law the pages follow: "fr", "eu", or one a bundle adds.')->end()
                ->scalarNode('governing_law')->isRequired()->cannotBeEmpty()
                    ->info('The country whose law governs the contracts, shown in the terms, e.g. "France".')->end()
                ->arrayNode('activities')->isRequired()
                    ->info('What the site sells and to whom; only the clauses that fit are assembled.')
                    ->requiresAtLeastOneElement()
                    ->enumPrototype()->values(self::ACTIVITIES)->end()
                ->end()

                ->arrayNode('site')->isRequired()
                    ->children()
                        ->scalarNode('name')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('url')->isRequired()
                            ->validate()
                                ->ifTrue(static fn ($v) => '' !== $v && (!\is_string($v) || !preg_match('#^https?://[^/\s]+#', $v)))
                                ->thenInvalid('site.url must be an absolute http(s) URL, got %s.')
                            ->end()
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('publisher')->isRequired()
                    ->children()
                        ->scalarNode('legal_name')->isRequired()->cannotBeEmpty()
                            ->info('Company name, or first and last name for a sole trader.')->end()
                        ->scalarNode('legal_form')->isRequired()->cannotBeEmpty()
                            ->info('e.g. "entrepreneur individuel (micro-entreprise)", "SAS", "GmbH".')->end()
                        ->scalarNode('company_id')->defaultNull()
                            ->info('The national company number (the SIRET in the "fr" pack).')->end()
                        ->scalarNode('registration')->defaultNull()
                            ->info('Trade register and number, e.g. "RCS Melun 123 456 789".')->end()
                        ->scalarNode('share_capital')->defaultNull()->end()
                        ->scalarNode('vat_number')->defaultNull()->end()
                        ->scalarNode('address')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('country')->defaultNull()->end()
                        ->scalarNode('email')->isRequired()
                            ->validate()
                                ->ifTrue(static fn ($v) => '' !== $v && false === filter_var($v, \FILTER_VALIDATE_EMAIL))
                                ->thenInvalid('publisher.email is not a valid address: %s.')
                            ->end()
                        ->end()
                        ->scalarNode('phone')->defaultNull()->end()
                        ->scalarNode('publication_director')->defaultNull()->end()
                    ->end()
                ->end()

                ->arrayNode('host')->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('name')->defaultNull()->end()
                        ->scalarNode('address')->defaultNull()->end()
                        ->scalarNode('phone')->defaultNull()->end()
                    ->end()
                ->end()

                ->arrayNode('mediator')
                    ->info('Consumer mediator or ADR body.')
                    ->canBeEnabled()
                    ->children()
                        ->scalarNode('name')->defaultNull()->end()
                        ->scalarNode('url')->defaultNull()->end()
                        ->scalarNode('address')->defaultNull()->end()
                    ->end()
                ->end()

                ->arrayNode('locales')
                    ->scalarPrototype()->end()
                    ->defaultValue(['fr', 'en'])
                    ->requiresAtLeastOneElement()
                ->end()

                ->scalarNode('parent_template')->defaultValue('@Fineprint/base.html.twig')
                    ->info('The template the bundle\'s layout extends. It must define the "title" and "body" blocks.')->end()

                ->append($pages)

                ->arrayNode('terms')->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('vat_exempt')->defaultFalse()
                            ->info('"fr" pack: prints "TVA non applicable, article 293 B du CGI".')->end()
                        ->scalarNode('payment_provider')->defaultNull()->info('e.g. "Stripe".')->end()
                        ->booleanNode('withdrawal_waiver')->defaultFalse()
                            ->info('Consumers waive withdrawal for digital content or services delivered at once; your checkout must collect that consent.')->end()
                        ->booleanNode('liability_limits')->defaultFalse()
                            ->info('Liability capped at the amount paid, towards business clients only.')->end()
                        ->scalarNode('court')->defaultNull()
                            ->info('Town whose courts hear disputes with business clients.')->end()
                        ->append($this->localized('subscription_cancellation')->info('How to cancel, e.g. "from your account page."'))
                        ->append($this->localized('marketplace_ranking')->info('The main parameters that rank offers (marketplaces).'))
                    ->end()
                ->end()

                ->arrayNode('privacy')->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('controller')->defaultNull()->info('Defaults to publisher.legal_name.')->end()
                        ->scalarNode('contact_email')->defaultNull()->info('Defaults to publisher.email.')->end()
                        ->scalarNode('dpo_email')->defaultNull()->end()
                        ->arrayNode('authority')->addDefaultsIfNotSet()
                            ->info('"eu" pack: your lead supervisory authority, named in the complaints section.')
                            ->children()
                                ->scalarNode('name')->defaultNull()->end()
                                ->scalarNode('url')->defaultNull()->end()
                            ->end()
                        ->end()
                        ->booleanNode('host_is_processor')->defaultTrue()
                            ->info('List the host among the processors, under the key "host".')->end()
                        ->arrayNode('processors')
                            ->useAttributeAsKey('id')
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('name')->isRequired()->cannotBeEmpty()->end()
                                    ->enumNode('role')->values(self::PROCESSOR_ROLES)->isRequired()->end()
                                    ->scalarNode('country')->isRequired()->cannotBeEmpty()->end()
                                    ->booleanNode('outside_eu')->defaultFalse()->end()
                                    ->append($this->localized('description'))
                                    ->append($this->localized('safeguards')->info('e.g. "EU-US Data Privacy Framework", "standard contractual clauses".'))
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('processing')
                            ->info('The processing activities: one entry per purpose.')
                            ->arrayPrototype()
                                ->children()
                                    ->append($this->localized('purpose', true))
                                    ->append($this->localized('data'))
                                    ->enumNode('legal_basis')->values(self::LEGAL_BASES)->isRequired()->end()
                                    ->append($this->localized('legitimate_interest'))
                                    ->append($this->localized('retention', true))
                                    ->arrayNode('processors')->scalarPrototype()->end()->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('cookies')->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('manage_url')->defaultNull()
                            ->info('Where visitors change their cookie choices, when you set non-essential cookies.')->end()
                        ->arrayNode('list')
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('name')->isRequired()->cannotBeEmpty()->end()
                                    ->enumNode('type')->values(self::COOKIE_TYPES)->defaultValue('essential')->end()
                                    ->scalarNode('provider')->defaultNull()->end()
                                    ->append($this->localized('purpose', true))
                                    ->append($this->localized('duration', true))
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()

                ->variableNode('options')->defaultValue([])
                    ->info('Free settings for third-party packs.')->end()
            ->end()

            ->validate()->always(static fn (array $c) => self::crossCheck($c))->end();

        return $tree;
    }

    private function page(string $page): ArrayNodeDefinition
    {
        $node = (new TreeBuilder($page))->getRootNode();
        $node
            ->canBeDisabled()
            ->children()
                ->arrayNode('path')
                    ->info('Path per locale, below /{locale}/.')
                    ->useAttributeAsKey('locale')
                    ->scalarPrototype()->end()
                    ->defaultValue(self::DEFAULT_PATHS[$page])
                ->end()
                ->scalarNode('last_updated')->defaultNull()->info('Shown at the bottom of the page, e.g. "2026-10-25".')->end()
                ->arrayNode('sections')->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('remove')->info('Clause ids to leave out.')->scalarPrototype()->end()->end()
                        ->arrayNode('override')
                            ->info('Clause id => a translation key (messages domain), a template (*.twig), or {body|template, title, domain}.')
                            ->useAttributeAsKey('id')
                            ->variablePrototype()
                                ->validate()
                                    ->ifTrue(static fn ($v) => !self::validOverride($v))
                                    ->thenInvalid('An override is a translation key, a template name ending in .twig, or a map with "body" or "template" and optional "title" and "domain", got %s.')
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('add')
                            ->info('Sections of your own. Plain text, blank lines start a new paragraph.')
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('id')->isRequired()->cannotBeEmpty()->end()
                                    ->append($this->localized('title', true))
                                    ->append($this->localized('body'))
                                    ->scalarNode('template')->defaultNull()->end()
                                    ->scalarNode('position')->defaultValue('end')
                                        ->info('start, end, before:<id> or after:<id>.')
                                        ->validate()
                                            ->ifTrue(static fn ($v) => !preg_match('/^(start|end|(before|after):[a-z0-9_]+)$/', (string) $v))
                                            ->thenInvalid('position must be start, end, before:<id> or after:<id>, got %s.')
                                        ->end()
                                    ->end()
                                ->end()
                                ->validate()
                                    ->ifTrue(static fn ($v) => (null === $v['body']) === (null === $v['template']))
                                    ->thenInvalid('An added section needs exactly one of "body" or "template": %s.')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $node;
    }

    private static function validOverride(mixed $v): bool
    {
        if (\is_string($v)) {
            return '' !== trim($v);
        }
        if (!\is_array($v) || [] !== array_diff(array_keys($v), ['body', 'template', 'title', 'domain'])) {
            return false;
        }

        return isset($v['body']) !== isset($v['template']);
    }

    /** A text given once for every locale ("text") or per locale ({fr: "…", en: "…"}). */
    private function localized(string $name, bool $required = false): NodeDefinition
    {
        $node = new VariableNodeDefinition($name);
        // Symfony 8 refuses a node that is both required and has a default value.
        if ($required) {
            $node->isRequired();
        } else {
            $node->defaultNull();
        }
        $node->validate()
            ->ifTrue(static function ($v) use ($required) {
                if (null === $v) {
                    return $required;
                }
                if (\is_string($v)) {
                    return '' === trim($v);
                }
                if (!\is_array($v) || [] === $v) {
                    return true;
                }
                foreach ($v as $locale => $text) {
                    if (!\is_string($locale) || !\is_string($text)) {
                        return true;
                    }
                }

                return false;
            })
            ->thenInvalid(\sprintf('"%s" must be a non-empty string or a map of locale to string, got %%s.', $name))
        ->end();

        return $node;
    }

    /** Checks that span several keys and hold whatever the jurisdiction. */
    private static function crossCheck(array $c): array
    {
        $fail = static fn (string $m) => throw new \InvalidArgumentException($m);

        foreach ([['site', 'url'], ['publisher', 'email']] as [$group, $key]) {
            if ('' === trim((string) $c[$group][$key])) {
                $fail(\sprintf('fineprint.%s.%s cannot be empty.', $group, $key));
            }
        }

        if ([] === array_intersect(['b2b', 'b2c'], $c['activities'])) {
            $fail('fineprint.activities must say who you sell to: b2b, b2c, or both.');
        }

        foreach ($c['locales'] as $locale) {
            if (!\is_string($locale) || !preg_match('/^[a-z]{2}(_[A-Z]{2})?$/', $locale)) {
                $fail(\sprintf('fineprint.locales: "%s" is not a locale code.', $locale));
            }
        }

        $seen = [];
        foreach (Fineprint::PAGES as $page) {
            if (!$c['pages'][$page]['enabled']) {
                continue;
            }
            foreach ($c['locales'] as $locale) {
                $path = trim((string) ($c['pages'][$page]['path'][$locale] ?? ''), '/');
                if ('' === $path) {
                    $fail(\sprintf('fineprint.pages.%s.path has no path for the locale "%s".', $page, $locale));
                }
                if (!preg_match('#^[A-Za-z0-9._~/-]+$#', $path)) {
                    $fail(\sprintf('fineprint.pages.%s.path.%s: "%s" has characters a URL path cannot hold as is.', $page, $locale, $path));
                }
                if (isset($seen[$locale][$path])) {
                    $fail(\sprintf('fineprint.pages.%s.path.%s: "%s" is already the path of the page "%s".', $page, $locale, $path, $seen[$locale][$path]));
                }
                $seen[$locale][$path] = $page;
            }
        }

        if ($c['mediator']['enabled'] && (empty($c['mediator']['name']) || empty($c['mediator']['url']))) {
            $fail('fineprint.mediator needs a name and a url.');
        }

        return $c;
    }
}
