<?php

declare(strict_types=1);

namespace Acencyril\FineprintBundle\Tests;

use Acencyril\FineprintBundle\Assembly\Validator;
use Acencyril\FineprintBundle\Pack\AbstractPack;
use Acencyril\FineprintBundle\Pack\Eu\EuPack;
use Acencyril\FineprintBundle\Pack\Fr\FrPack;
use Acencyril\FineprintBundle\Pack\PackInterface;
use Acencyril\FineprintBundle\Tests\Fixtures\TestKernel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** The rules each pack enforces, and the section checks every pack gets. */
final class PackValidationTest extends TestCase
{
    private static function errors(PackInterface $pack, array $config): array
    {
        return Validator::errors($pack, ConfigurationTest::process($config));
    }

    public function testTheBaseConfigurationsAreValid(): void
    {
        self::assertSame([], self::errors(new FrPack(), TestKernel::baseConfig()));
        self::assertSame([], self::errors(new EuPack(), TestKernel::euConfig()));
    }

    public static function frInvalid(): iterable
    {
        $base = TestKernel::baseConfig();

        $c = $base; unset($c['publisher']['company_id']);
        yield 'no SIRET' => [$c, 'fineprint.publisher.company_id is required by the "fr" pack'];

        $c = $base; $c['publisher']['company_id'] = '1234';
        yield 'short SIRET' => [$c, 'must have 14 digits'];

        $c = $base; unset($c['publisher']['publication_director']);
        yield 'no publication director' => [$c, 'fineprint.publisher.publication_director is required'];

        $c = $base; unset($c['host']['phone']);
        yield 'host without phone' => [$c, 'fineprint.host.phone is required by the "fr" pack'];

        $c = $base; unset($c['mediator']);
        yield 'consumers without mediator' => [$c, 'declare a consumer mediator'];

        $c = $base; $c['governing_law'] = 'Belgique';
        yield 'another law' => [$c, 'use the "eu" pack'];

        $c = $base; unset($c['terms']['marketplace_ranking']);
        yield 'marketplace without ranking' => [$c, 'terms.marketplace_ranking'];

        $c = $base; $c['activities'] = ['b2c', 'physical_goods'];
        yield 'waiver without digital or services' => [$c, 'withdrawal_waiver only applies'];

        $c = $base; $c['privacy']['processing'][1]['processors'] = ['stripe'];
        yield 'undeclared processor' => [$c, 'names the processor "stripe"'];

        $c = $base; unset($c['privacy']['processing'][0]['legitimate_interest']);
        yield 'legitimate interest undescribed' => [$c, 'describe the interest'];

        $c = $base; unset($c['privacy']['processors']['ai']['safeguards']);
        yield 'transfer without safeguard' => [$c, 'outside the EEA'];

        $c = $base; $c['privacy']['processors']['host'] = ['name' => 'X', 'role' => 'hosting', 'country' => 'France'];
        yield 'reserved host key' => [$c, '"host" is reserved'];

        $c = $base; $c['privacy']['processing'] = [];
        yield 'privacy page without processing' => [$c, 'processing is empty'];

        $c = $base; $c['cookies']['list'][] = ['name' => '_ga', 'type' => 'analytics', 'purpose' => 'Stats', 'duration' => '13 mois'];
        yield 'analytics cookie without manage_url' => [$c, 'cookies.manage_url'];
    }

    #[DataProvider('frInvalid')]
    public function testTheFrPackRules(array $config, string $message): void
    {
        self::assertStringContainsString($message, implode("\n", self::errors(new FrPack(), $config)));
    }

    public function testTheFrPackDropsConsumerRulesForBusinessOnlySites(): void
    {
        $c = TestKernel::baseConfig();
        $c['activities'] = ['b2b', 'services'];
        unset($c['mediator'], $c['terms']['withdrawal_waiver'], $c['terms']['marketplace_ranking'], $c['terms']['subscription_cancellation']);

        self::assertSame([], self::errors(new FrPack(), $c));
    }

    public static function euInvalid(): iterable
    {
        $base = TestKernel::euConfig();

        $c = $base; unset($c['publisher']['country']);
        yield 'no country' => [$c, 'fineprint.publisher.country is required by the "eu" pack'];

        $c = $base; $c['terms']['vat_exempt'] = true;
        yield 'French VAT mention' => [$c, 'only exists in the "fr" pack'];

        $c = $base; unset($c['terms']['marketplace_ranking']);
        yield 'marketplace without ranking' => [$c, 'Directive 2011/83/EU, art. 6a'];

        $c = $base; $c['activities'] = ['b2b', 'digital', 'subscription', 'marketplace'];
        yield 'waiver without consumers' => [$c, 'withdrawal_waiver only applies'];

        $c = $base; $c['privacy']['processing'][0]['processors'] = ['host'];
        yield 'host as processor without a host' => [$c, 'names the processor "host"'];

        $c = $base; unset($c['privacy']['processors']['ai']['safeguards']);
        yield 'transfer without safeguard' => [$c, 'outside the EEA'];
    }

    #[DataProvider('euInvalid')]
    public function testTheEuPackRules(array $config, string $message): void
    {
        self::assertStringContainsString($message, implode("\n", self::errors(new EuPack(), $config)));
    }

    public function testTheEuPackNeedsNoHostNoDirectorNoMediatorAndAnyLaw(): void
    {
        $c = TestKernel::euConfig();
        $c['governing_law'] = 'Belgium';

        self::assertSame([], self::errors(new EuPack(), $c));
    }

    public static function sectionsInvalid(): iterable
    {
        yield 'remove an unknown clause' => [['remove' => ['nope']], 'sections.remove: the "fr" pack has no clause "nope"'];
        yield 'override an unknown clause' => [['override' => ['nope' => 'x']], 'sections.override: the "fr" pack has no clause "nope"'];
        yield 'add over a clause id' => [['add' => [['id' => 'law', 'title' => 'T', 'body' => 'B']]], 'the id "law" is already taken'];
        yield 'add twice the same id' => [['add' => [['id' => 'x', 'title' => 'T', 'body' => 'B'], ['id' => 'x', 'title' => 'T', 'body' => 'B']]], 'the id "x" is already taken'];
        yield 'anchor on nothing' => [['add' => [['id' => 'x', 'title' => 'T', 'body' => 'B', 'position' => 'after:nope']]], 'position "after:nope" names no clause'];
    }

    #[DataProvider('sectionsInvalid')]
    public function testSectionOperationsAreCheckedAgainstThePack(array $sections, string $message): void
    {
        $c = TestKernel::baseConfig();
        $c['pages']['terms']['sections'] = $sections;

        self::assertStringContainsString($message, implode("\n", self::errors(new FrPack(), $c)));
    }

    public function testAPageLeftEmptyIsRefused(): void
    {
        $c = TestKernel::baseConfig();
        $c['pages']['cookies']['sections']['remove'] = ['intro', 'list', 'browser'];

        self::assertStringContainsString('fineprint.pages.cookies: the "fr" pack has nothing to show', implode("\n", self::errors(new FrPack(), $c)));
    }

    public function testEnvValuesSkipFormatChecks(): void
    {
        $c = ConfigurationTest::process(TestKernel::baseConfig());
        $c['publisher']['company_id'] = AbstractPack::DYNAMIC;
        $c['governing_law'] = AbstractPack::DYNAMIC;

        self::assertSame([], Validator::errors(new FrPack(), $c));
    }

    public function testEveryClauseDeclaresItsPack(): void
    {
        foreach ([new FrPack(), new EuPack()] as $pack) {
            foreach ($pack->clauses() as $clause) {
                self::assertSame([$pack->name()], $clause->packs, $clause->id);
            }
        }
    }
}
