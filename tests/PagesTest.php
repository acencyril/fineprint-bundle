<?php

declare(strict_types=1);

namespace Acencyril\FineprintBundle\Tests;

use Acencyril\FineprintBundle\Sitemap\FineprintUrls;

/** Routing, links, sitemap, layout, overrides and escaping. */
final class PagesTest extends KernelTestCase
{
    public function testPagesRenderInTheRequestedLocale(): void
    {
        $client = self::browse();
        $crawler = $client->request('GET', '/fr/mentions-legales');

        self::assertResponseIsSuccessful();
        self::assertSame('fr', $client->getResponse()->headers->get('Content-Language'));
        self::assertSame('Mentions légales', $crawler->filter('h1')->text());
        self::assertSame('Mentions légales — Exemple', $crawler->filter('title')->text());
        self::assertStringContainsString('123 456 789 00012', $crawler->filter('main')->text());

        $crawler = $client->request('GET', '/en/privacy');
        self::assertSame('Privacy policy', $crawler->filter('h1')->text());
        self::assertStringContainsString('Last updated: 2026-10-25', $crawler->filter('main')->text());
        self::assertStringContainsString('Hébergeur SAS, Mailer Co', $crawler->filter('main')->text());
    }

    public function testDisabledPagesAnswer404AndAreNotLinked(): void
    {
        $client = self::browse([], 'disabled');
        foreach (['/fr/cgv', '/en/terms-of-sale', '/fr/cookies', '/en/cookies'] as $url) {
            $client->request('GET', $url);
            self::assertResponseStatusCodeSame(404, $url);
        }
        $crawler = $client->request('GET', '/fr/confidentialite');
        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter('main a[href="/fr/cookies"]'), 'no link to a disabled page');
        self::assertCount(0, $crawler->filter('#cookies'), 'the clause pointing to it is left out');
    }

    public function testAPageIsNotServedUnderTheOtherLocale(): void
    {
        self::browse()->request('GET', '/en/mentions-legales');
        self::assertResponseStatusCodeSame(404);
    }

    public function testPathsAreConfigurable(): void
    {
        $client = self::browse(['pages' => ['terms' => ['path' => ['fr' => 'conditions-de-vente', 'en' => 'terms']]]]);
        $client->request('GET', '/fr/conditions-de-vente');
        self::assertResponseIsSuccessful();
        $client->request('GET', '/en/terms');
        self::assertResponseIsSuccessful();
        $client->request('GET', '/fr/cgv');
        self::assertResponseStatusCodeSame(404);
    }

    public function testClauseLinksPointToTheOtherPages(): void
    {
        $crawler = self::browse()->request('GET', '/fr/cgv');
        self::assertSame('mentions légales', $crawler->filter('#seller a[href="/fr/mentions-legales"]')->text());
        self::assertCount(1, $crawler->filter('#personal-data a[href="/fr/confidentialite"]'));
    }

    public function testFooterLinksListTheEnabledPagesInTheCurrentLocale(): void
    {
        $links = self::browse()->request('GET', '/en/footer')->filter('nav.fineprint-links a');
        self::assertSame(['Legal notice', 'Terms of sale', 'Privacy', 'Cookies'], $links->each(fn ($a) => $a->text()));
        self::assertSame(['/en/legal-notice', '/en/terms-of-sale', '/en/privacy', '/en/cookies'], $links->each(fn ($a) => $a->attr('href')));

        $links = self::browse([], 'disabled')->request('GET', '/fr/footer')->filter('nav.fineprint-links a');
        self::assertSame(['/fr/mentions-legales', '/fr/confidentialite'], $links->each(fn ($a) => $a->attr('href')));
    }

    public function testSitemapServiceListsAbsoluteUrlsWithAlternates(): void
    {
        self::browse([], 'disabled');
        $urls = self::getContainer()->get(FineprintUrls::class)->all();

        self::assertSame(['https://example.test/fr/mentions-legales', 'https://example.test/en/legal-notice', 'https://example.test/fr/confidentialite', 'https://example.test/en/privacy'], array_column($urls, 'loc'));
        self::assertSame(['fr' => 'https://example.test/fr/confidentialite', 'en' => 'https://example.test/en/privacy'], $urls[2]['alternates']);
        self::assertSame($urls, self::getContainer()->get('fineprint.urls')->all());
    }

    public function testTemplateOverridesStillWork(): void
    {
        $client = self::browse();
        $crawler = $client->request('GET', '/fr/cookies');
        self::assertSame('Site-specific browser advice.', $crawler->filter('#overridden')->text(), 'page template');
        self::assertStringContainsString('PHPSESSID', $crawler->filter('main')->text());

        $crawler = $client->request('GET', '/fr/mentions-legales');
        self::assertSame('Hébergeur SAS — overridden host clause.', $crawler->filter('#overridden-host')->text(), 'clause template');
    }

    public function testConfiguredTextsAreEscapedAndPercentSignsSurvive(): void
    {
        $client = self::browse([
            'site' => ['name' => '100 % <b>Site</b>', 'url' => 'https://example.test'],
            'pages' => ['privacy' => ['sections' => ['add' => [['id' => 'extra', 'title' => 'Extra', 'body' => "First.\n\nSecond <paragraph>."]]]]],
        ]);
        $crawler = $client->request('GET', '/fr/confidentialite');
        $html = $client->getResponse()->getContent();

        self::assertStringContainsString('100 % &lt;b&gt;Site&lt;/b&gt;', $html);
        self::assertStringContainsString('Second &lt;paragraph&gt;.', $html);
        self::assertCount(2, $crawler->filter('#extra p'));
    }

    public function testTheBundleLayoutUsesTheSiteParentTemplate(): void
    {
        $crawler = self::browse(['parent_template' => 'site_base.html.twig'])->request('GET', '/en/legal-notice');

        self::assertSame('Legal notice — Exemple', $crawler->filter('title')->text());
        self::assertCount(1, $crawler->filter('header#site-header + main.fineprint'));
    }

    public function testPublisherFactsMayComeFromEnvironmentVariables(): void
    {
        $_SERVER['FINEPRINT_TEST_SIRET'] = '98765432100019';
        $_SERVER['FINEPRINT_TEST_EMAIL'] = 'env@example.test';
        try {
            $client = self::browse(['publisher' => ['company_id' => '%env(FINEPRINT_TEST_SIRET)%', 'email' => '%env(FINEPRINT_TEST_EMAIL)%']]);
            $text = $client->request('GET', '/fr/mentions-legales')->filter('main')->text();
        } finally {
            unset($_SERVER['FINEPRINT_TEST_SIRET'], $_SERVER['FINEPRINT_TEST_EMAIL']);
        }

        self::assertStringContainsString('98765432100019', $text);
        self::assertStringContainsString('env@example.test', $text);
    }

    public function testAnInvalidConfigurationStopsTheBuild(): void
    {
        $this->expectException(\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException::class);
        $this->expectExceptionMessage('fineprint.host.phone is required by the "fr" pack.');
        self::browse(['host' => ['phone' => null]]);
    }
}
