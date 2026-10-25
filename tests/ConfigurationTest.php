<?php

declare(strict_types=1);

namespace Acencyril\FineprintBundle\Tests;

use Acencyril\FineprintBundle\DependencyInjection\Configuration;
use Acencyril\FineprintBundle\Fineprint;
use Acencyril\FineprintBundle\Tests\Fixtures\TestKernel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

/** The shape of the configuration, whatever the jurisdiction. */
final class ConfigurationTest extends TestCase
{
    public static function process(array $config): array
    {
        return Fineprint::prepare((new Processor())->processConfiguration(new Configuration(), [$config]));
    }

    public function testTheBaseConfigurationIsValidAndFillsDefaults(): void
    {
        $c = self::process(TestKernel::baseConfig());

        self::assertSame(['fr', 'en'], $c['locales']);
        self::assertSame(['fr' => 'mentions-legales', 'en' => 'legal-notice'], $c['pages']['legal_notice']['path']);
        self::assertSame(['remove' => [], 'override' => [], 'add' => []], $c['pages']['terms']['sections']);
        self::assertSame(['host', 'mail', 'ai'], array_keys($c['privacy']['processor_list']), 'the host comes first');
        self::assertSame('@Fineprint/base.html.twig', $c['parent_template']);
    }

    public function testOverridesAreNormalised(): void
    {
        $c = TestKernel::baseConfig();
        $c['pages']['terms']['sections']['override'] = ['law' => 'legal.law', 'liability' => 'legal/liability.html.twig', 'price_both' => ['body' => 'x', 'domain' => 'legal']];
        $o = self::process($c)['pages']['terms']['sections']['override'];

        self::assertSame(['body' => 'legal.law', 'template' => null, 'title' => null, 'domain' => 'messages'], $o['law']);
        self::assertSame('legal/liability.html.twig', $o['liability']['template']);
        self::assertSame('legal', $o['price_both']['domain']);
    }

    public static function invalid(): iterable
    {
        $base = TestKernel::baseConfig();

        $c = $base; unset($c['jurisdiction']);
        yield 'no jurisdiction' => [$c, 'jurisdiction'];

        $c = $base; unset($c['governing_law']);
        yield 'no governing law' => [$c, 'governing_law'];

        $c = $base; $c['activities'] = ['digital'];
        yield 'nobody to sell to' => [$c, 'must say who you sell to'];

        $c = $base; $c['activities'] = ['b2b', 'crypto'];
        yield 'unknown activity' => [$c, 'crypto'];

        $c = $base; $c['publisher']['email'] = 'not-an-email';
        yield 'bad email' => [$c, 'publisher.email is not a valid address'];

        $c = $base; $c['publisher']['email'] = '';
        yield 'empty email' => [$c, 'fineprint.publisher.email cannot be empty'];

        $c = $base; $c['site']['url'] = 'example.test';
        yield 'relative site url' => [$c, 'site.url must be an absolute http(s) URL'];

        $c = $base; $c['mediator'] = ['name' => 'Médiateur'];
        yield 'mediator without url' => [$c, 'mediator needs a name and a url'];

        $c = $base; $c['privacy']['processing'][0]['legal_basis'] = 'because';
        yield 'unknown legal basis' => [$c, 'legal_basis'];

        $c = $base; $c['privacy']['processing'][0]['purpose'] = ['fr' => ['nested']];
        yield 'malformed localized text' => [$c, '"purpose" must be a non-empty string or a map'];

        $c = $base; $c['pages']['terms']['path'] = ['fr' => 'cgv'];
        yield 'missing locale path' => [$c, 'fineprint.pages.terms.path has no path for the locale "en"'];

        $c = $base; $c['pages']['cookies']['path'] = ['fr' => 'cgv', 'en' => 'cookies'];
        yield 'duplicate path' => [$c, 'is already the path of the page "terms"'];

        $c = $base; $c['locales'] = ['french'];
        yield 'bad locale' => [$c, '"french" is not a locale code'];

        $c = $base; $c['pages']['terms']['sections']['override'] = ['law' => ['body' => 'a', 'template' => 'b.twig']];
        yield 'override with body and template' => [$c, 'An override is'];

        $c = $base; $c['pages']['terms']['sections']['override'] = ['law' => ['text' => 'a']];
        yield 'override with an unknown key' => [$c, 'An override is'];

        $c = $base; $c['pages']['terms']['sections']['add'] = [['id' => 'x', 'title' => 'X']];
        yield 'added section without body' => [$c, 'exactly one of "body" or "template"'];

        $c = $base; $c['pages']['terms']['sections']['add'] = [['id' => 'x', 'title' => 'X', 'body' => 'y', 'position' => 'middle']];
        yield 'bad position' => [$c, 'position must be start, end'];
    }

    #[DataProvider('invalid')]
    public function testInvalidConfigurationsAreRefusedWithAClearMessage(array $config, string $message): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage($message);

        self::process($config);
    }
}
