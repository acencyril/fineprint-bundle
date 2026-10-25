<?php

declare(strict_types=1);

namespace Acencyril\FineprintBundle\Tests;

use Acencyril\FineprintBundle\Sitemap\FineprintUrls;
use PHPUnit\Framework\Attributes\DataProvider;

final class PagesTest extends KernelTestCase
{
    public static function pages(): iterable
    {
        yield 'legal notice fr' => ['/fr/mentions-legales', 'fr', 'Mentions légales', ['Jeanne Martin', '123 456 789 00012', 'Hébergeur SAS', '+33 1 00 00 00 00', 'Directeur de la publication', 'FR00123456789']];
        yield 'legal notice en' => ['/en/legal-notice', 'en', 'Legal notice', ['Jeanne Martin', 'Publication director:', 'Hébergeur SAS']];
        yield 'terms fr' => ['/fr/cgv', 'fr', 'Conditions générales de vente', ['quatorze jours', 'L221-28', 'Formulaire de rétractation', 'Médiateur Exemple', 'Melun', 'Stripe', 'L215-1-1', 'depuis votre espace client.', 'L441-10']];
        yield 'terms en' => ['/en/terms-of-sale', 'en', 'Terms of sale', ['fourteen days', 'Withdrawal form', 'Médiateur Exemple', 'from your account page.']];
        yield 'privacy fr' => ['/fr/confidentialite', 'fr', 'Politique de confidentialité', ['Responsable du traitement', 'Répondre aux messages', 'répondre aux demandes', 'Hébergeur SAS, Mailer Co', 'clauses contractuelles types', 'CNIL', 'article 85', 'Section ajoutée', 'Dernière mise à jour : 2026-10-25']];
        yield 'privacy en' => ['/en/privacy', 'en', 'Privacy policy', ['Data controller', 'Answering messages', '3 years', 'standard contractual clauses', 'CNIL', 'Added section', 'Last updated: 2026-10-25']];
        yield 'cookies fr' => ['/fr/cookies', 'fr', 'Cookies', ['PHPSESSID', 'Strictement nécessaire', 'aucun consentement']];
        yield 'cookies en' => ['/en/cookies', 'en', 'Cookies', ['PHPSESSID', 'Strictly necessary', 'no consent']];
    }

    #[DataProvider('pages')]
    public function testEveryPageRendersInBothLanguages(string $url, string $locale, string $title, array $expected): void
    {
        $client = self::browse();
        $crawler = $client->request('GET', $url);

        self::assertResponseIsSuccessful();
        self::assertSame($locale, $client->getResponse()->headers->get('Content-Language'));
        self::assertSame($title, $crawler->filter('h1')->text());
        self::assertSame($locale, $crawler->filter('main')->attr('lang'));
        $text = $crawler->filter('main')->text();
        foreach ($expected as $needle) {
            self::assertStringContainsString($needle, $text);
        }
        self::assertStringNotContainsStringIgnoringCase('certifi', $text);
        self::assertStringNotContainsString('terms.', $text, 'no untranslated key');
        self::assertStringNotContainsString('privacy.', $text, 'no untranslated key');
    }

    public function testDisabledPagesAnswer404(): void
    {
        $client = self::browse([], 'disabled');
        foreach (['/fr/cgv', '/en/terms-of-sale', '/fr/cookies', '/en/cookies'] as $url) {
            $client->request('GET', $url);
            self::assertResponseStatusCodeSame(404, $url);
        }
        $crawler = $client->request('GET', '/fr/mentions-legales');
        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('main a[href="/fr/confidentialite"]'));
        self::assertCount(0, $crawler->filter('main a[href="/fr/cookies"]'), 'no link to a disabled page');
    }

    public function testAPageIsNotServedUnderTheOtherLocale(): void
    {
        $client = self::browse();
        $client->request('GET', '/en/mentions-legales');
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

    public function testFooterLinksListTheEnabledPagesInTheCurrentLocale(): void
    {
        $client = self::browse();
        $links = $client->request('GET', '/en/footer')->filter('nav.fineprint-links a');
        self::assertSame(['Legal notice', 'Terms of sale', 'Privacy', 'Cookies'], $links->each(fn ($a) => $a->text()));
        self::assertSame(['/en/legal-notice', '/en/terms-of-sale', '/en/privacy', '/en/cookies'], $links->each(fn ($a) => $a->attr('href')));

        $client = self::browse([], 'disabled');
        $links = $client->request('GET', '/fr/footer')->filter('nav.fineprint-links a');
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

    public function testATemplateOverrideReplacesOneBlockOnly(): void
    {
        $client = self::browse();
        $crawler = $client->request('GET', '/fr/cookies');
        self::assertSame('Site-specific browser advice.', $crawler->filter('#overridden')->text());
        self::assertStringContainsString('PHPSESSID', $crawler->filter('main')->text());
    }

    public function testConfiguredTextsAreEscapedAndPercentSignsSurvive(): void
    {
        $client = self::browse(['site' => ['name' => '100 % <b>Site</b>', 'url' => 'https://example.test']]);
        $crawler = $client->request('GET', '/fr/confidentialite');
        $html = $client->getResponse()->getContent();

        self::assertStringContainsString('100 % &lt;b&gt;Site&lt;/b&gt;', $html);
        self::assertStringContainsString('Second &lt;paragraph&gt;.', $html);
        self::assertCount(2, $crawler->filter('.fineprint-extra p'));
    }

    public function testPublisherFactsMayComeFromEnvironmentVariables(): void
    {
        $_SERVER['FINEPRINT_TEST_SIRET'] = '98765432100019';
        $_SERVER['FINEPRINT_TEST_EMAIL'] = 'env@example.test';
        try {
            $client = self::browse(['publisher' => ['siret' => '%env(FINEPRINT_TEST_SIRET)%', 'email' => '%env(FINEPRINT_TEST_EMAIL)%']]);
            $text = $client->request('GET', '/fr/mentions-legales')->filter('main')->text();
        } finally {
            unset($_SERVER['FINEPRINT_TEST_SIRET'], $_SERVER['FINEPRINT_TEST_EMAIL']);
        }

        self::assertStringContainsString('98765432100019', $text);
        self::assertStringContainsString('env@example.test', $text);
    }

    public function testBusinessOnlyTermsHaveNoConsumerClauses(): void
    {
        $client = self::browse(['terms' => ['audience' => 'b2b', 'vat_exempt' => true]]);
        $text = $client->request('GET', '/fr/cgv')->filter('main')->text();

        self::assertStringContainsString('réservées aux professionnels', $text);
        self::assertStringContainsString('L221-3', $text);
        self::assertStringContainsString('293 B', $text);
        self::assertStringContainsString('limitée au montant payé', $text);
        foreach (['Formulaire de rétractation', 'Médiation', 'Garanties légales', 'R631-3', 'L215-1-1'] as $absent) {
            self::assertStringNotContainsString($absent, $text);
        }
    }

    public function testConsumerOnlyTermsHaveNoBusinessClauses(): void
    {
        $client = self::browse(['terms' => ['audience' => 'b2c']]);
        $text = $client->request('GET', '/en/terms-of-sale')->filter('main')->text();

        self::assertStringContainsString('including all taxes', $text);
        self::assertStringContainsString('Mediation', $text);
        foreach (['L441-10', 'Liability', 'courts of Melun', 'L221-3'] as $absent) {
            self::assertStringNotContainsString($absent, $text);
        }
    }

    public function testTheBundleLayoutUsesTheSiteParentTemplate(): void
    {
        $client = self::browse(['parent_template' => 'site_base.html.twig']);
        $crawler = $client->request('GET', '/en/legal-notice');

        self::assertSame('Legal notice — Exemple', $crawler->filter('title')->text());
        self::assertCount(1, $crawler->filter('header#site-header'));
        self::assertCount(1, $crawler->filter('header#site-header + main.fineprint'));
    }
}
