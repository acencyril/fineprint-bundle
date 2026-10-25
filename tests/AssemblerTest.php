<?php

declare(strict_types=1);

namespace Acencyril\FineprintBundle\Tests;

use Acencyril\FineprintBundle\Assembly\Assembler;
use Acencyril\FineprintBundle\Pack\Eu\EuPack;
use Acencyril\FineprintBundle\Pack\Fr\FrPack;
use Acencyril\FineprintBundle\Pack\PackInterface;
use Acencyril\FineprintBundle\Tests\Fixtures\TestKernel;
use PHPUnit\Framework\TestCase;

/** Which clauses a page gets: filtered by activity and configuration, then edited. */
final class AssemblerTest extends TestCase
{
    /** @return list<string> */
    private static function ids(PackInterface $pack, array $config, string $page = 'terms'): array
    {
        return array_column(Assembler::plan($pack, ConfigurationTest::process($config), $page), 'id');
    }

    private static function with(array $activities, array $changes = []): array
    {
        $c = TestKernel::baseConfig();
        $c['activities'] = $activities;

        return array_replace_recursive($c, $changes);
    }

    public function testBusinessOnlySalesHaveNoConsumerClauses(): void
    {
        foreach ([new FrPack(), new EuPack()] as $pack) {
            $ids = self::ids($pack, self::with(['b2b']));
            foreach (['audience_b2b', 'price_b2b', 'late_payment', 'no_withdrawal', 'liability', 'law_court'] as $id) {
                self::assertContains($id, $ids, $pack->name());
            }
            foreach (['withdrawal', 'withdrawal_form', 'guarantees', 'mediation', 'law_consumers', 'delivery', 'subscription', 'marketplace', 'digital'] as $id) {
                self::assertNotContains($id, $ids, $pack->name());
            }
        }
    }

    public function testConsumerOnlySalesHaveNoBusinessClauses(): void
    {
        $ids = self::ids(new FrPack(), self::with(['b2c', 'digital']));

        foreach (['audience_b2c', 'price_b2c', 'withdrawal', 'withdrawal_waiver', 'withdrawal_form', 'guarantees', 'mediation', 'law_consumers', 'digital'] as $id) {
            self::assertContains($id, $ids);
        }
        foreach (['late_payment', 'no_withdrawal', 'no_withdrawal_businesses', 'liability', 'law_court', 'means', 'withdrawal_goods', 'withdrawal_partial'] as $id) {
            self::assertNotContains($id, $ids);
        }
    }

    public function testEachActivityBringsItsClauses(): void
    {
        $pack = new FrPack();
        self::assertSame(['delivery', 'delivery_consumers', 'withdrawal_goods'], array_values(array_intersect(self::ids($pack, self::with(['b2c', 'physical_goods'])), ['delivery', 'delivery_consumers', 'withdrawal_goods'])));
        self::assertContains('withdrawal_partial', self::ids($pack, self::with(['b2c', 'services'])));
        self::assertContains('means', self::ids($pack, self::with(['b2b', 'services'])));
        self::assertContains('subscription_consumers', self::ids($pack, self::with(['b2c', 'subscription', 'digital'])));
        self::assertNotContains('subscription_consumers', self::ids($pack, self::with(['b2b', 'subscription'])));
        self::assertContains('marketplace', self::ids($pack, self::with(['b2c', 'marketplace'])));
        self::assertNotContains('guarantees', self::ids($pack, self::with(['b2c', 'services'])), 'no conformity guarantee for plain services');
    }

    public function testConfigurationConditions(): void
    {
        $pack = new FrPack();
        $vat = self::ids($pack, self::with(['b2b'], ['terms' => ['vat_exempt' => true]]));
        self::assertContains('price_vat_exempt', $vat);
        self::assertNotContains('price_b2b', $vat);

        $plain = self::with(['b2b'], ['terms' => ['liability_limits' => false, 'court' => null, 'payment_provider' => null]]);
        foreach (['liability', 'law_court', 'payment_provider'] as $id) {
            self::assertNotContains($id, self::ids($pack, $plain));
        }

        $noPrivacy = self::with(['b2b'], ['pages' => ['privacy' => ['enabled' => false]]]);
        self::assertNotContains('personal_data', self::ids($pack, $noPrivacy));
        self::assertNotContains('personal_data', self::ids($pack, $noPrivacy, 'legal_notice'));
    }

    public function testTheEuPackShowsTheHostAndTheMediatorOnlyWhenDeclared(): void
    {
        $pack = new EuPack();
        self::assertNotContains('host', self::ids($pack, TestKernel::euConfig(), 'legal_notice'));
        self::assertNotContains('mediation', self::ids($pack, TestKernel::euConfig()));

        $c = TestKernel::euConfig();
        $c['host'] = ['name' => 'Host GmbH'];
        $c['mediator'] = ['name' => 'ADR body', 'url' => 'https://adr.example.test'];
        self::assertContains('host', self::ids($pack, $c, 'legal_notice'));
        self::assertContains('mediation', self::ids($pack, $c));
    }

    public function testRemoveOverrideAndAdd(): void
    {
        $c = self::with(['b2b'], ['pages' => ['terms' => ['sections' => [
            'remove' => ['liability', 'withdrawal'],
            'override' => ['law' => 'legal.law'],
            'add' => [
                ['id' => 'first', 'title' => 'First', 'body' => 'x', 'position' => 'start'],
                ['id' => 'after_order', 'title' => 'After order', 'body' => 'x', 'position' => 'after:order'],
                ['id' => 'before_law', 'title' => 'Before law', 'template' => 'x.html.twig', 'position' => 'before:law'],
                ['id' => 'filtered_anchor', 'title' => 'Anchor', 'body' => 'x', 'position' => 'after:withdrawal_form'],
                ['id' => 'last', 'title' => 'Last', 'body' => 'x'],
            ],
        ]]]]);
        $plan = Assembler::plan(new FrPack(), ConfigurationTest::process($c), 'terms');
        $ids = array_column($plan, 'id');

        self::assertSame('first', $ids[0]);
        self::assertSame('after_order', $ids[array_search('order', $ids, true) + 1]);
        self::assertSame('before_law', $ids[array_search('law', $ids, true) - 1]);
        self::assertSame(['filtered_anchor', 'last'], \array_slice($ids, -2), 'an anchor filtered out sends the section to the end');
        self::assertNotContains('liability', $ids);
        self::assertSame('legal.law', $plan[array_search('law', $ids, true)]['override']['body']);
    }
}
