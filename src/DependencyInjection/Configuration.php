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
 * The bundle supplies the wording the law asks for; the facts (who publishes, who hosts,
 * what is collected and why) are declared by the site. A page never states a fact the
 * site did not declare, which is why the cross-checks at the end refuse a configuration
 * that would leave a mandatory mention empty.
 *
 * Format checks let "" through and the emptiness of those fields is checked at the root:
 * an %env()% value is checked against an empty dummy at build time, and Symfony refuses
 * env values on a node that both validates and cannot be empty.
 */
final class Configuration implements ConfigurationInterface
{
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
                    ->info('The legal notice of the loi pour la confiance dans l\'économie numérique (LCEN).')
                    ->children()
                        ->scalarNode('legal_name')->isRequired()->cannotBeEmpty()
                            ->info('Company name, or first and last name for a sole trader.')->end()
                        ->scalarNode('legal_form')->isRequired()->cannotBeEmpty()
                            ->info('e.g. "entrepreneur individuel (micro-entreprise)", "SAS", "SARL".')->end()
                        ->scalarNode('siret')->isRequired()
                            ->validate()
                                ->ifTrue(static fn ($v) => '' !== $v && !preg_match('/^\d{14}$/', preg_replace('/\s+/', '', (string) $v)))
                                ->thenInvalid('publisher.siret must have 14 digits, got %s.')
                            ->end()
                        ->end()
                        ->scalarNode('registration')->defaultNull()
                            ->info('Trade or crafts register entry, e.g. "RCS Melun 123 456 789". Required for companies registered there.')->end()
                        ->scalarNode('share_capital')->defaultNull()
                            ->info('e.g. "1 000 €". Required for companies with share capital.')->end()
                        ->scalarNode('vat_number')->defaultNull()->end()
                        ->scalarNode('address')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('email')->isRequired()
                            ->validate()
                                ->ifTrue(static fn ($v) => '' !== $v && false === filter_var($v, \FILTER_VALIDATE_EMAIL))
                                ->thenInvalid('publisher.email is not a valid address: %s.')
                            ->end()
                        ->end()
                        ->scalarNode('phone')->defaultNull()
                            ->info('The LCEN asks professional publishers for a phone number.')->end()
                        ->scalarNode('publication_director')->isRequired()->cannotBeEmpty()->end()
                    ->end()
                ->end()

                ->arrayNode('host')->isRequired()
                    ->info('The LCEN requires the host\'s name, address and phone number.')
                    ->children()
                        ->scalarNode('name')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('address')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('phone')->isRequired()->cannotBeEmpty()->end()
                    ->end()
                ->end()

                ->arrayNode('mediator')
                    ->info('Consumer mediator (Code de la consommation, L612-1). Required when you sell to consumers.')
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
                        ->enumNode('audience')->values(['b2b', 'b2c', 'both'])->defaultValue('b2b')
                            ->info('b2b: businesses only. b2c or both: consumer rules apply (withdrawal, mediator, legal guarantees).')->end()
                        ->booleanNode('vat_exempt')->defaultFalse()
                            ->info('VAT franchise: prints "TVA non applicable, article 293 B du CGI".')->end()
                        ->scalarNode('payment_provider')->defaultNull()->info('e.g. "Stripe".')->end()
                        ->booleanNode('digital_services')->defaultFalse()->end()
                        ->arrayNode('subscriptions')->canBeEnabled()
                            ->children()
                                ->append($this->localized('cancellation')->info('How to cancel, e.g. "from your account page". Shown after the generic rule.'))
                            ->end()
                        ->end()
                        ->booleanNode('immediate_digital_content')->defaultFalse()
                            ->info('Consumers waive the right of withdrawal for content or services delivered at once (L221-28).')->end()
                        ->booleanNode('liability_limits')->defaultFalse()
                            ->info('Liability capped at the amount paid, towards business clients only.')->end()
                        ->scalarNode('court')->defaultNull()
                            ->info('Town whose courts have jurisdiction over disputes with business clients, e.g. "Melun".')->end()
                    ->end()
                ->end()

                ->arrayNode('privacy')->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('controller')->defaultNull()->info('Defaults to publisher.legal_name.')->end()
                        ->scalarNode('contact_email')->defaultNull()->info('Defaults to publisher.email.')->end()
                        ->scalarNode('dpo_email')->defaultNull()->end()
                        ->booleanNode('host_is_processor')->defaultTrue()
                            ->info('List the host among the processors, under the key "host".')->end()
                        ->arrayNode('processors')
                            ->useAttributeAsKey('id')
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('name')->isRequired()->cannotBeEmpty()->end()
                                    ->enumNode('role')->values(self::PROCESSOR_ROLES)->isRequired()->end()
                                    ->scalarNode('country')->isRequired()->cannotBeEmpty()->end()
                                    ->booleanNode('outside_eu')->defaultFalse()
                                        ->info('Data leaves the European Economic Area: describe the safeguard.')->end()
                                    ->append($this->localized('description'))
                                    ->append($this->localized('safeguards')->info('e.g. "EU-US Data Privacy Framework", "standard contractual clauses".'))
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('activities')
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
                ->arrayNode('sections')
                    ->info('Extra sections appended to the page. Plain text; blank lines start a new paragraph.')
                    ->arrayPrototype()
                        ->children()
                            ->append($this->localized('title', true))
                            ->append($this->localized('body', true))
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $node;
    }

    /** A text given once for every locale ("text") or per locale ({fr: "…", en: "…"}). */
    private function localized(string $name, bool $required = false): NodeDefinition
    {
        $node = new VariableNodeDefinition($name);
        $node->defaultNull();
        if ($required) {
            $node->isRequired();
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

    /** Checks that span several keys: each one guards a mention the law makes mandatory. */
    private static function crossCheck(array $c): array
    {
        $fail = static fn (string $m) => throw new \InvalidArgumentException($m);

        foreach ([['site', 'url'], ['publisher', 'siret'], ['publisher', 'email']] as [$group, $key]) {
            if ('' === trim((string) $c[$group][$key])) {
                $fail(\sprintf('fineprint.%s.%s cannot be empty.', $group, $key));
            }
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
        if ($c['pages']['terms']['enabled'] && 'b2b' !== $c['terms']['audience'] && !$c['mediator']['enabled']) {
            $fail('fineprint.terms.audience includes consumers: declare a consumer mediator under fineprint.mediator (Code de la consommation, L612-1).');
        }

        if ($c['pages']['privacy']['enabled'] && [] === $c['privacy']['activities']) {
            $fail('fineprint.privacy.activities is empty: declare what the site processes (server logs and contact emails count), or disable the privacy page.');
        }

        $processors = $c['privacy']['processors'];
        if ($c['privacy']['host_is_processor'] && isset($processors['host'])) {
            $fail('fineprint.privacy.processors: "host" is reserved for the host, which is listed on its own (set host_is_processor: false to declare it yourself).');
        }
        foreach ($processors as $id => $p) {
            if ($p['outside_eu'] && null === $p['safeguards']) {
                $fail(\sprintf('fineprint.privacy.processors.%s sends data outside the EEA: say under "safeguards" what frames the transfer (GDPR, art. 13.1.f).', $id));
            }
        }
        $known = array_keys($processors);
        if ($c['privacy']['host_is_processor']) {
            $known[] = 'host';
        }
        foreach ($c['privacy']['activities'] as $i => $a) {
            foreach ($a['processors'] as $id) {
                if (!\in_array($id, $known, true)) {
                    $fail(\sprintf('fineprint.privacy.activities.%d names the processor "%s", which is not declared under fineprint.privacy.processors.', $i, $id));
                }
            }
            if ('legitimate_interests' === $a['legal_basis'] && null === $a['legitimate_interest']) {
                $fail(\sprintf('fineprint.privacy.activities.%d rests on legitimate interests: describe the interest under "legitimate_interest" (GDPR, art. 13.1.d).', $i));
            }
        }

        $nonEssential = array_filter($c['cookies']['list'], static fn (array $k) => 'essential' !== $k['type']);
        if ([] !== $nonEssential && null === $c['cookies']['manage_url']) {
            $fail('fineprint.cookies: non-essential cookies need consent that can be withdrawn at any time: set cookies.manage_url to the page where visitors change their choice.');
        }

        return $c;
    }
}
