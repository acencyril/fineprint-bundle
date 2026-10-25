<?php

declare(strict_types=1);

namespace Acencyril\FineprintBundle\Tests\Fixtures;

use Acencyril\FineprintBundle\FineprintBundle;
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
 * A bare application: FrameworkBundle, TwigBundle and Fineprint. The environment name
 * picks the configuration ("test" = everything on, "disabled" = some pages off), and a
 * test may pass extra Fineprint settings that are merged on top.
 */
final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function __construct(string $environment, bool $debug, private readonly array $fineprint = [])
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
        return \dirname(__DIR__, 2).'/var/cache/'.$this->environment.'-'.md5(serialize($this->fineprint));
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

        $config = self::baseConfig();
        if ('disabled' === $this->environment) {
            $config['pages'] = ['terms' => false, 'cookies' => ['enabled' => false]];
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

    /** A complete, valid configuration — the one the README shows. */
    public static function baseConfig(): array
    {
        return [
            'site' => ['name' => 'Exemple', 'url' => 'https://example.test'],
            'publisher' => [
                'legal_name' => 'Jeanne Martin',
                'legal_form' => 'entrepreneur individuel (micro-entreprise)',
                'siret' => '123 456 789 00012',
                'vat_number' => 'FR00123456789',
                'address' => '1 rue de l\'Exemple, 75001 Paris, France',
                'email' => 'contact@example.test',
                'publication_director' => 'Jeanne Martin',
            ],
            'host' => ['name' => 'Hébergeur SAS', 'address' => '2 avenue du Serveur, 59100 Roubaix, France', 'phone' => '+33 1 00 00 00 00'],
            'mediator' => ['name' => 'Médiateur Exemple', 'url' => 'https://mediateur.example.test'],
            'pages' => ['privacy' => ['last_updated' => '2026-10-25', 'sections' => [
                ['title' => ['fr' => 'Section ajoutée', 'en' => 'Added section'], 'body' => "First paragraph.\n\nSecond <paragraph>."],
            ]]],
            'terms' => [
                'audience' => 'both',
                'payment_provider' => 'Stripe',
                'digital_services' => true,
                'subscriptions' => ['cancellation' => ['fr' => 'depuis votre espace client.', 'en' => 'from your account page.']],
                'immediate_digital_content' => true,
                'liability_limits' => true,
                'court' => 'Melun',
            ],
            'privacy' => [
                'processors' => [
                    'mail' => ['name' => 'Mailer Co', 'role' => 'email', 'country' => 'France'],
                    'ai' => ['name' => 'AI Inc.', 'role' => 'ai', 'country' => 'États-Unis', 'outside_eu' => true, 'safeguards' => ['fr' => 'clauses contractuelles types', 'en' => 'standard contractual clauses']],
                ],
                'activities' => [
                    ['purpose' => ['fr' => 'Répondre aux messages', 'en' => 'Answering messages'], 'data' => 'email', 'legal_basis' => 'legitimate_interests', 'legitimate_interest' => ['fr' => 'répondre aux demandes', 'en' => 'answering requests'], 'retention' => ['fr' => '3 ans', 'en' => '3 years'], 'processors' => ['host', 'mail']],
                    ['purpose' => ['fr' => 'Analyse par IA', 'en' => 'AI analysis'], 'legal_basis' => 'contract', 'retention' => '30 jours / 30 days', 'processors' => ['ai']],
                ],
            ],
            'cookies' => ['list' => [
                ['name' => 'PHPSESSID', 'purpose' => ['fr' => 'Session', 'en' => 'Session'], 'duration' => ['fr' => 'Session', 'en' => 'Session']],
            ]],
        ];
    }
}
