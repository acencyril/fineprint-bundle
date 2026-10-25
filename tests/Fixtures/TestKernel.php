<?php

declare(strict_types=1);

namespace Acencyril\FineprintBundle\Tests\Fixtures;

use Acencyril\FineprintBundle\FineprintBundle;
use Acencyril\FineprintBundle\Tests\Fixtures\Pack\XxPack;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

/**
 * A bare application: FrameworkBundle, TwigBundle and Fineprint. The environment picks
 * the configuration ("test" = fr pack, everything on; "disabled" = some pages off;
 * "thirdparty" = a pack registered by the application), and a test may pass a full
 * Fineprint configuration of its own, or settings merged on top of the base one.
 */
final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function __construct(string $environment, bool $debug, private readonly array $fineprint = [], private readonly bool $replace = false)
    {
        parent::__construct($environment, $debug);
    }

    public function registerBundles(): iterable
    {
        return [new FrameworkBundle(), new TwigBundle(), new FineprintBundle()];
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    public function getCacheDir(): string
    {
        return \dirname(__DIR__, 2).'/var/cache/'.$this->environment.'-'.md5(serialize([$this->fineprint, $this->replace]));
    }

    public function getLogDir(): string
    {
        return \dirname(__DIR__, 2).'/var/log';
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->loadFromExtension('framework', [
            'secret' => 'test',
            'test' => true,
            'http_method_override' => false,
            'handle_all_throwables' => true,
            'php_errors' => ['log' => true],
            'router' => ['utf8' => true],
            'translator' => ['default_path' => __DIR__.'/translations', 'fallbacks' => ['fr']],
            'default_locale' => 'fr',
        ]);
        $container->register('logger', NullLogger::class);
        $container->register(FooterController::class)->setArguments([new Reference('twig')])->setPublic(true);
        $container->loadFromExtension('twig', ['default_path' => __DIR__.'/templates', 'strict_variables' => true]);

        if ($this->replace) {
            $container->loadFromExtension('fineprint', $this->fineprint);

            return;
        }

        $config = self::baseConfig();
        if ('disabled' === $this->environment) {
            $config['pages'] = ['terms' => false, 'cookies' => ['enabled' => false]];
        }
        if ('thirdparty' === $this->environment) {
            // Registered like an application service: autoconfiguration tags it.
            $container->register(XxPack::class)->setAutoconfigured(true);
            $config['jurisdiction'] = 'xx';
            $config['options'] = ['xx_id' => 'XX-42'];
        }
        $container->loadFromExtension('fineprint', $config);
        if ([] !== $this->fineprint) {
            $container->loadFromExtension('fineprint', $this->fineprint);
        }
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('.', 'fineprint');
        $routes->add('footer', '/{_locale}/footer')->controller(FooterController::class)->requirements(['_locale' => 'fr|en']);
    }

    /** A complete, valid "fr" configuration with every activity. */
    public static function baseConfig(): array
    {
        return [
            'jurisdiction' => 'fr',
            'governing_law' => 'France',
            'activities' => ['b2b', 'b2c', 'digital', 'subscription', 'physical_goods', 'marketplace', 'services'],
            'site' => ['name' => 'Exemple', 'url' => 'https://example.test'],
            'publisher' => [
                'legal_name' => 'Jeanne Martin',
                'legal_form' => 'entrepreneur individuel (micro-entreprise)',
                'company_id' => '123 456 789 00012',
                'vat_number' => 'FR00123456789',
                'address' => '1 rue de l\'Exemple, 75001 Paris',
                'country' => 'France',
                'email' => 'contact@example.test',
                'publication_director' => 'Jeanne Martin',
            ],
            'host' => ['name' => 'Hébergeur SAS', 'address' => '2 avenue du Serveur, 59100 Roubaix, France', 'phone' => '+33 1 00 00 00 00'],
            'mediator' => ['name' => 'Médiateur Exemple', 'url' => 'https://mediateur.example.test'],
            'pages' => ['privacy' => ['last_updated' => '2026-10-25']],
            'terms' => [
                'payment_provider' => 'Stripe',
                'withdrawal_waiver' => true,
                'liability_limits' => true,
                'court' => 'Melun',
                'subscription_cancellation' => ['fr' => 'depuis votre espace client.', 'en' => 'from your account page.'],
                'marketplace_ranking' => ['fr' => 'la date de publication', 'en' => 'the publication date'],
            ],
            'privacy' => [
                'processors' => [
                    'mail' => ['name' => 'Mailer Co', 'role' => 'email', 'country' => 'France'],
                    'ai' => ['name' => 'AI Inc.', 'role' => 'ai', 'country' => 'États-Unis', 'outside_eu' => true, 'safeguards' => ['fr' => 'clauses contractuelles types', 'en' => 'standard contractual clauses']],
                ],
                'processing' => [
                    ['purpose' => ['fr' => 'Répondre aux messages', 'en' => 'Answering messages'], 'data' => 'email', 'legal_basis' => 'legitimate_interests', 'legitimate_interest' => ['fr' => 'répondre aux demandes', 'en' => 'answering requests'], 'retention' => ['fr' => '3 ans', 'en' => '3 years'], 'processors' => ['host', 'mail']],
                    ['purpose' => ['fr' => 'Analyse par IA', 'en' => 'AI analysis'], 'legal_basis' => 'contract', 'retention' => '30 jours / 30 days', 'processors' => ['ai']],
                ],
            ],
            'cookies' => ['list' => [
                ['name' => 'PHPSESSID', 'purpose' => ['fr' => 'Session', 'en' => 'Session'], 'duration' => ['fr' => 'Session', 'en' => 'Session']],
            ]],
        ];
    }

    /** The same site under the "eu" pack: a German publisher, no host, an authority. */
    public static function euConfig(): array
    {
        $c = self::baseConfig();
        $c['jurisdiction'] = 'eu';
        $c['governing_law'] = 'Germany';
        $c['publisher'] = ['legal_name' => 'Beispiel GmbH', 'legal_form' => 'GmbH', 'registration' => 'Amtsgericht Berlin, HRB 000000', 'address' => 'Beispielstraße 1, 10115 Berlin', 'country' => 'Germany', 'email' => 'info@example.test'];
        $c['host'] = [];
        $c['mediator'] = ['enabled' => false];
        $c['terms']['court'] = 'Berlin';
        $c['privacy']['processing'][0]['processors'] = ['mail'];
        $c['privacy']['authority'] = ['name' => 'Example Authority', 'url' => 'https://authority.example.test'];

        return $c;
    }
}
