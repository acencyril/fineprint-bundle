<?php

declare(strict_types=1);

namespace Acencyril\FineprintBundle\Tests;

/** pages.<page>.sections: remove, override and add, as visitors see them. */
final class SectionsTest extends KernelTestCase
{
    public function testRemoveOverrideAndAddRender(): void
    {
        $client = self::browse(['pages' => ['terms' => ['sections' => [
            'remove' => ['marketplace', 'delivery', 'delivery_consumers'],
            'override' => [
                'law' => 'legal.law',
                'liability' => 'legal/liability.html.twig',
                'order' => ['body' => 'legal.law', 'title' => ['fr' => 'Commander', 'en' => 'How to order']],
            ],
            'add' => [
                ['id' => 'quotes', 'title' => ['fr' => 'Devis', 'en' => 'Quotes'], 'body' => ['fr' => 'Un devis vaut 30 jours.', 'en' => 'A quote is valid for 30 days.'], 'position' => 'after:order'],
                ['id' => 'warranty', 'title' => 'Warranty', 'template' => 'legal/liability.html.twig', 'position' => 'start'],
            ],
        ]]]]);

        $crawler = $client->request('GET', '/en/terms-of-sale');
        $ids = $crawler->filter('main section')->each(fn ($s) => $s->attr('id'));

        self::assertSame('warranty', $ids[0]);
        self::assertSame('quotes', $ids[array_search('order', $ids, true) + 1]);
        foreach (['marketplace', 'delivery', 'delivery-consumers'] as $removed) {
            self::assertNotContains($removed, $ids);
        }
        self::assertSame('Governing law and disputes', $crawler->filter('#law h2')->text(), 'an override keeps the clause title');
        self::assertSame('Our own governing law clause, for Exemple.', $crawler->filter('#law p')->text());
        self::assertSame('Custom liability for Exemple (liability, fineprint_fr).', $crawler->filter('#liability #custom-liability')->text());
        self::assertSame('How to order', $crawler->filter('#order h2')->text());
        self::assertSame('A quote is valid for 30 days.', $crawler->filter('#quotes p')->text());

        $crawler = $client->request('GET', '/fr/cgv');
        self::assertSame('Notre propre clause de droit applicable, pour Exemple.', $crawler->filter('#law p')->text());
        self::assertSame('Devis', $crawler->filter('#quotes h2')->text());
    }
}
