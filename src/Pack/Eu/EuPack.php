<?php

declare(strict_types=1);

namespace Acencyril\FineprintBundle\Pack\Eu;

use Acencyril\FineprintBundle\Pack\AbstractPack;
use Acencyril\FineprintBundle\Pack\GdprChecks;

/**
 * Any EU member state, citing EU law only: the e-Commerce Directive (2000/31/EC) for the
 * legal notice, the Consumer Rights Directive (2011/83/EU), the sale of goods and digital
 * content Directives (2019/771, 2019/770), the ADR Directive (2013/11/EU) and the late
 * payment Directive (2011/7/EU) for the terms, the GDPR and the ePrivacy Directive for
 * privacy and cookies. The governing law and the publisher's country come from the site.
 *
 * National rules that go further (a register entry format, a mandatory mediator, a
 * consumer cancellation button) are not covered: add them as sections, or write a pack
 * for that country.
 *
 * Texts: translations/fineprint_eu.{fr,en}.yaml. Shared templates: templates/clauses/.
 */
final class EuPack extends AbstractPack
{
    use GdprChecks;

    public function name(): string
    {
        return 'eu';
    }

    public function clauses(): array
    {
        $t = static fn (string $page, string $id, ?string $title = null, mixed ...$o) => [$page, $id, $title, $o];
        $list = [
            // Legal notice (Directive 2000/31/EC, art. 5)
            $t('legal_notice', 'intro', null, body: 'legal_notice.intro'),
            $t('legal_notice', 'publisher', 'legal_notice.publisher.title', template: '@Fineprint/clauses/publisher.html.twig'),
            $t('legal_notice', 'host', 'legal_notice.host.title', template: '@Fineprint/clauses/host.html.twig', when: ['host.name']),
            $t('legal_notice', 'intellectual_property', 'legal_notice.intellectual_property.title', body: 'legal_notice.intellectual_property.body'),
            $t('legal_notice', 'personal_data', 'legal_notice.personal_data.title', body: 'legal_notice.personal_data.body', when: ['page:privacy'], link: 'privacy'),
            $t('legal_notice', 'contact', 'legal_notice.contact.title', template: '@Fineprint/clauses/contact.html.twig'),

            // Terms of sale
            $t('terms', 'scope', 'terms.scope.title', body: 'terms.scope.body'),
            $t('terms', 'audience_b2b', null, body: 'terms.audience.b2b', activities: ['b2b'], excludes: ['b2c']),
            $t('terms', 'audience_b2c', null, body: 'terms.audience.b2c', activities: ['b2c'], excludes: ['b2b']),
            $t('terms', 'audience_both', null, body: 'terms.audience.both', activities: ['b2b', 'b2c']),
            $t('terms', 'seller', null, body: 'terms.seller.body', link: 'legal_notice'),
            $t('terms', 'marketplace', 'terms.marketplace.title', body: 'terms.marketplace.body', activities: ['marketplace']),
            $t('terms', 'order', 'terms.order.title', body: 'terms.order.body'),
            $t('terms', 'price_b2b', 'terms.price.title', body: 'terms.price.b2b', activities: ['b2b'], excludes: ['b2c']),
            $t('terms', 'price_b2c', 'terms.price.title', body: 'terms.price.b2c', activities: ['b2c'], excludes: ['b2b']),
            $t('terms', 'price_both', 'terms.price.title', body: 'terms.price.both', activities: ['b2b', 'b2c']),
            $t('terms', 'payment', null, body: 'terms.payment.body'),
            $t('terms', 'payment_provider', null, body: 'terms.payment.provider', when: ['terms.payment_provider']),
            $t('terms', 'late_payment', null, body: 'terms.payment.late', activities: ['b2b']),
            $t('terms', 'digital', 'terms.digital.title', body: 'terms.digital.body', activities: ['digital']),
            $t('terms', 'digital_client', null, body: 'terms.digital.client', activities: ['digital']),
            $t('terms', 'services', 'terms.services.title', body: 'terms.services.body', activities: ['services']),
            $t('terms', 'means', null, body: 'terms.services.means', activities: ['b2b'], anyOf: ['digital', 'services']),
            $t('terms', 'delivery', 'terms.delivery.title', body: 'terms.delivery.body', activities: ['physical_goods']),
            $t('terms', 'delivery_consumers', null, body: 'terms.delivery.consumers', activities: ['physical_goods', 'b2c']),
            $t('terms', 'subscription', 'terms.subscription.title', body: 'terms.subscription.body', activities: ['subscription']),
            $t('terms', 'subscription_cancellation', null, body: 'terms.subscription.how', activities: ['subscription'], when: ['terms.subscription_cancellation']),
            $t('terms', 'withdrawal', 'terms.withdrawal.title', body: 'terms.withdrawal.body', activities: ['b2c']),
            $t('terms', 'withdrawal_goods', null, body: 'terms.withdrawal.goods', activities: ['b2c', 'physical_goods']),
            $t('terms', 'withdrawal_partial', null, body: 'terms.withdrawal.partial', activities: ['b2c', 'services']),
            $t('terms', 'withdrawal_waiver', null, body: 'terms.withdrawal.waiver', activities: ['b2c'], when: ['terms.withdrawal_waiver']),
            $t('terms', 'no_withdrawal', 'terms.withdrawal.title', body: 'terms.withdrawal.businesses_only', activities: ['b2b'], excludes: ['b2c']),
            $t('terms', 'no_withdrawal_businesses', null, body: 'terms.withdrawal.businesses_also', activities: ['b2b', 'b2c']),
            $t('terms', 'withdrawal_form', 'terms.withdrawal_form.title', template: '@Fineprint/clauses/withdrawal_form.html.twig', activities: ['b2c']),
            $t('terms', 'guarantees', 'terms.guarantees.title', template: '@Fineprint/clauses/guarantees.html.twig', activities: ['b2c'], anyOf: ['physical_goods', 'digital']),
            $t('terms', 'liability', 'terms.liability.title', body: 'terms.liability.body', activities: ['b2b'], when: ['terms.liability_limits']),
            $t('terms', 'personal_data', 'terms.personal_data.title', body: 'terms.personal_data.body', when: ['page:privacy'], link: 'privacy'),
            $t('terms', 'mediation', 'terms.mediation.title', template: '@Fineprint/clauses/mediator.html.twig', activities: ['b2c'], when: ['mediator.enabled']),
            $t('terms', 'law', 'terms.law.title', body: 'terms.law.body'),
            $t('terms', 'law_court', null, body: 'terms.law.court', activities: ['b2b'], when: ['terms.court']),
            $t('terms', 'law_consumers', null, body: 'terms.law.consumers', activities: ['b2c']),

            // Privacy policy (GDPR)
            $t('privacy', 'intro', null, body: 'privacy.intro'),
            $t('privacy', 'controller', 'privacy.controller.title', template: '@Fineprint/clauses/controller.html.twig'),
            $t('privacy', 'processing', 'privacy.processing.title', template: '@Fineprint/clauses/processing.html.twig'),
            $t('privacy', 'processors', 'privacy.processors.title', template: '@Fineprint/clauses/processors.html.twig', when: ['privacy.processor_list']),
            $t('privacy', 'rights', 'privacy.rights.title', body: 'privacy.rights.body'),
            $t('privacy', 'complaint', 'privacy.complaint.title', body: 'privacy.complaint.body'),
            $t('privacy', 'complaint_authority', null, body: 'privacy.complaint.authority', when: ['privacy.authority.name']),
            $t('privacy', 'cookies', 'privacy.cookies.title', body: 'privacy.cookies.body', when: ['page:cookies'], link: 'cookies'),

            // Cookies (Directive 2002/58/EC, art. 5(3))
            $t('cookies', 'intro', null, body: 'cookies.intro'),
            $t('cookies', 'list', 'cookies.list.title', template: '@Fineprint/clauses/cookie_list.html.twig'),
            $t('cookies', 'browser', 'cookies.browser.title', body: 'cookies.browser.body'),
        ];

        return array_map(fn (array $c) => $this->clause($c[1], $c[0], ...['title' => $c[2], ...$c[3]]), $list);
    }

    public function requiredFields(array $config): array
    {
        // Directive 2000/31/EC, art. 5: the geographic address, hence the country.
        return ['publisher.country'];
    }

    public function validate(array $c): array
    {
        $errors = [...$this->gdprErrors($c), ...$this->activityErrors($c)];
        if ($c['terms']['vat_exempt']) {
            $errors[] = 'fineprint.terms.vat_exempt prints a French tax mention and only exists in the "fr" pack: state your national VAT exemption in a section instead.';
        }

        return $errors;
    }
}
