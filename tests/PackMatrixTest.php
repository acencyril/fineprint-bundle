<?php

declare(strict_types=1);

namespace Acencyril\FineprintBundle\Tests;

use Acencyril\FineprintBundle\Tests\Fixtures\TestKernel;
use PHPUnit\Framework\Attributes\DataProvider;

/** Every pack, with each typical mix of activities, renders its four pages in both languages. */
final class PackMatrixTest extends KernelTestCase
{
    private const PAGES = [
        'fr' => ['/fr/mentions-legales', '/fr/cgv', '/fr/confidentialite', '/fr/cookies'],
        'en' => ['/en/legal-notice', '/en/terms-of-sale', '/en/privacy', '/en/cookies'],
    ];

    private const ACTIVITY_SETS = [
        'b2b' => ['b2b'],
        'b2b services' => ['b2b', 'services'],
        'b2b digital subscription' => ['b2b', 'digital', 'subscription'],
        'b2c' => ['b2c'],
        'b2c physical goods' => ['b2c', 'physical_goods'],
        'b2c digital subscription' => ['b2c', 'digital', 'subscription'],
        'b2c marketplace' => ['b2c', 'marketplace', 'physical_goods'],
        'both, everything' => ['b2b', 'b2c', 'digital', 'subscription', 'physical_goods', 'marketplace', 'services'],
    ];

    public static function combinations(): iterable
    {
        foreach (['fr', 'eu'] as $pack) {
            foreach (self::ACTIVITY_SETS as $label => $activities) {
                yield $pack.' × '.$label => [$pack, $activities];
            }
        }
    }

    #[DataProvider('combinations')]
    public function testEveryPageRenders(string $pack, array $activities): void
    {
        $config = 'eu' === $pack ? TestKernel::euConfig() : TestKernel::baseConfig();
        $config['activities'] = $activities;
        if (!array_intersect(['digital', 'services'], $activities) || !\in_array('b2c', $activities, true)) {
            $config['terms']['withdrawal_waiver'] = false;
        }
        if (!\in_array('subscription', $activities, true)) {
            unset($config['terms']['subscription_cancellation']);
        }

        $client = self::browseWith($config);
        foreach (self::PAGES as $locale => $urls) {
            foreach ($urls as $url) {
                $crawler = $client->request('GET', $url);
                self::assertResponseIsSuccessful($url);
                $text = $crawler->filter('main')->text();
                self::assertNotSame('', $crawler->filter('main h1')->text(), $url);
                self::assertGreaterThan(1, $crawler->filter('main section')->count(), $url);
                self::assertDoesNotMatchRegularExpression('/\b(page|label|legal_notice|terms|privacy|cookies)\.[a-z_]+/', $text, 'untranslated key on '.$url);
                self::assertStringNotContainsString('%', $text, 'unreplaced placeholder on '.$url);
                self::assertStringNotContainsStringIgnoringCase('certifi', $text, $url);
                if ('eu' === $pack) {
                    self::assertDoesNotMatchRegularExpression('/\b[LRD]\d{3}-\d+|CNIL|LCEN|Code de la consommation|Consumer Code/', $text, 'French citation in the eu pack on '.$url);
                }
            }
        }

        $terms = $client->request('GET', '/en/terms-of-sale')->filter('main')->text();
        self::assertStringContainsString('eu' === $pack ? 'law of Germany' : 'law of France', $terms, 'the governing law is shown');
        self::assertSame(\in_array('b2c', $activities, true), str_contains($terms, 'fourteen days'));
    }
}
