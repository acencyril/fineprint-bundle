<?php

declare(strict_types=1);

namespace Acencyril\FineprintBundle\Tests\Fixtures\Pack;

use Acencyril\FineprintBundle\Pack\AbstractPack;

/** A pack an application or another bundle could ship: one clause per page. */
final class XxPack extends AbstractPack
{
    public function name(): string
    {
        return 'xx';
    }

    public function clauses(): array
    {
        return [
            $this->clause('imprint', 'legal_notice', title: 'imprint.title', body: 'imprint.body'),
            $this->clause('b2b_terms', 'terms', title: 'terms.title', body: 'terms.b2b', activities: ['b2b']),
            $this->clause('b2c_terms', 'terms', title: 'terms.title', body: 'terms.b2c', activities: ['b2c']),
            $this->clause('privacy', 'privacy', body: 'privacy.body'),
            $this->clause('cookies', 'cookies', template: '@Fineprint/clauses/cookie_list.html.twig'),
        ];
    }

    public function requiredFields(array $config): array
    {
        return ['options.xx_id'];
    }

    public function validate(array $config): array
    {
        return 'Narnia' === $config['governing_law'] ? ['fineprint.governing_law: the "xx" pack does not cover Narnia.'] : [];
    }
}
