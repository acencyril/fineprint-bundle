<?php

declare(strict_types=1);

namespace Acencyril\FineprintBundle\Tests;

use Acencyril\FineprintBundle\Tests\Fixtures\TestKernel;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/** A pack registered by the application (tests/Fixtures/Pack/XxPack) works like a built-in one. */
final class ThirdPartyPackTest extends KernelTestCase
{
    public function testItsClausesTranslationsAndRoutesAreUsed(): void
    {
        $client = self::browse([], 'thirdparty');

        $crawler = $client->request('GET', '/en/legal-notice');
        self::assertResponseIsSuccessful();
        self::assertSame('XX imprint en', $crawler->filter('h1')->text());
        self::assertStringContainsString('Published by Jeanne Martin under the law of France.', $crawler->filter('main')->text());

        $text = $client->request('GET', '/fr/cgv')->filter('main')->text();
        self::assertStringContainsString('XX business terms.', $text);
        self::assertStringContainsString('XX consumer terms.', $text);

        self::assertStringContainsString('XX essential only', $client->request('GET', '/en/cookies')->filter('main')->text());
        self::assertSame(['Imprint', 'Terms', 'Privacy', 'Cookies'], $client->request('GET', '/en/footer')->filter('nav a')->each(fn ($a) => $a->text()));
    }

    public function testItsRequiredFieldsAndRulesStopTheBuild(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('fineprint.options.xx_id is required by the "xx" pack.');
        self::browse(['options' => ['xx_id' => null]], 'thirdparty');
    }

    public function testItsOwnChecksStopTheBuild(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('the "xx" pack does not cover Narnia');
        self::browse(['governing_law' => 'Narnia'], 'thirdparty');
    }

    public function testAnUnknownJurisdictionNamesTheAvailablePacks(): void
    {
        $config = TestKernel::baseConfig();
        $config['jurisdiction'] = 'de';

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('no pack named "de" (available: fr, eu)');
        self::browseWith($config);
    }
}
