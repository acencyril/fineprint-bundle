<?php

declare(strict_types=1);

namespace Acencyril\FineprintBundle\Tests;

use Acencyril\FineprintBundle\DependencyInjection\Configuration;
use Acencyril\FineprintBundle\Tests\Fixtures\TestKernel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

final class ConfigurationTest extends TestCase
{
    private static function process(array $config): array
    {
        return (new Processor())->processConfiguration(new Configuration(), [$config]);
    }

    public function testTheReadmeConfigurationIsValidAndFillsDefaults(): void
    {
        $c = self::process(TestKernel::baseConfig());

        self::assertSame(['fr', 'en'], $c['locales']);
        self::assertSame(['fr' => 'mentions-legales', 'en' => 'legal-notice'], $c['pages']['legal_notice']['path']);
        self::assertTrue($c['pages']['cookies']['enabled']);
        self::assertTrue($c['mediator']['enabled']);
        self::assertTrue($c['terms']['subscriptions']['enabled']);
        self::assertSame('@Fineprint/base.html.twig', $c['parent_template']);
    }

    public function testAMinimalBusinessSiteNeedsNoMediator(): void
    {
        $c = TestKernel::baseConfig();
        unset($c['mediator'], $c['terms']);

        self::assertSame('b2b', self::process($c)['terms']['audience']);
    }

    public function testADisabledPageNeedsNoPathAndNoActivities(): void
    {
        $c = TestKernel::baseConfig();
        $c['locales'] = ['fr', 'de'];
        $c['pages'] = ['legal_notice' => ['path' => ['fr' => 'mentions', 'de' => 'impressum']], 'terms' => false, 'privacy' => false, 'cookies' => false];
        $c['privacy'] = [];

        self::assertFalse(self::process($c)['pages']['privacy']['enabled']);
    }

    public static function invalid(): iterable
    {
        $base = TestKernel::baseConfig();

        $c = $base; unset($c['host']['phone']);
        yield 'host without phone' => [$c, 'phone'];

        $c = $base; $c['publisher']['siret'] = '1234';
        yield 'short SIRET' => [$c, 'publisher.siret must have 14 digits'];

        $c = $base; $c['publisher']['email'] = 'not-an-email';
        yield 'bad email' => [$c, 'publisher.email is not a valid address'];

        $c = $base; $c['publisher']['email'] = '';
        yield 'empty email' => [$c, 'fineprint.publisher.email cannot be empty'];

        $c = $base; $c['site']['url'] = 'example.test';
        yield 'relative site url' => [$c, 'site.url must be an absolute http(s) URL'];

        $c = $base; unset($c['mediator']);
        yield 'consumers without mediator' => [$c, 'declare a consumer mediator'];

        $c = $base; $c['mediator'] = ['name' => 'Médiateur'];
        yield 'mediator without url' => [$c, 'mediator needs a name and a url'];

        $c = $base; $c['terms']['audience'] = 'everyone';
        yield 'unknown audience' => [$c, 'audience'];

        $c = $base; $c['privacy']['activities'][1]['processors'] = ['stripe'];
        yield 'undeclared processor' => [$c, 'names the processor "stripe"'];

        $c = $base; unset($c['privacy']['activities'][0]['legitimate_interest']);
        yield 'legitimate interest undescribed' => [$c, 'describe the interest'];

        $c = $base; unset($c['privacy']['processors']['ai']['safeguards']);
        yield 'transfer without safeguard' => [$c, 'outside the EEA'];

        $c = $base; $c['privacy']['processors']['host'] = ['name' => 'X', 'role' => 'hosting', 'country' => 'France'];
        yield 'reserved host key' => [$c, '"host" is reserved'];

        $c = $base; $c['privacy']['activities'] = [];
        yield 'privacy page without activities' => [$c, 'activities is empty'];

        $c = $base; $c['privacy']['activities'][0]['legal_basis'] = 'because';
        yield 'unknown legal basis' => [$c, 'legal_basis'];

        $c = $base; unset($c['privacy']['activities'][0]['retention']);
        yield 'activity without retention' => [$c, 'retention'];

        $c = $base; $c['privacy']['activities'][0]['purpose'] = ['fr' => ['nested']];
        yield 'malformed localized text' => [$c, '"purpose" must be a non-empty string or a map'];

        $c = $base; $c['cookies']['list'][] = ['name' => '_ga', 'type' => 'analytics', 'purpose' => 'Stats', 'duration' => '13 mois'];
        yield 'analytics cookie without manage_url' => [$c, 'cookies.manage_url'];

        $c = $base; $c['pages']['terms'] = ['path' => ['fr' => 'cgv']];
        yield 'missing locale path' => [$c, 'fineprint.pages.terms.path has no path for the locale "en"'];

        $c = $base; $c['pages']['cookies'] = ['path' => ['fr' => 'cgv', 'en' => 'cookies']];
        yield 'duplicate path' => [$c, 'is already the path of the page "terms"'];

        $c = $base; $c['pages']['cookies'] = ['path' => ['fr' => 'coo kies', 'en' => 'cookies']];
        yield 'unsafe path' => [$c, 'characters a URL path cannot hold'];

        $c = $base; $c['locales'] = ['french'];
        yield 'bad locale' => [$c, '"french" is not a locale code'];
    }

    #[DataProvider('invalid')]
    public function testInvalidConfigurationsAreRefusedWithAClearMessage(array $config, string $message): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage($message);

        self::process($config);
    }
}
