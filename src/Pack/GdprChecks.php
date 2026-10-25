<?php

declare(strict_types=1);

namespace Acencyril\FineprintBundle\Pack;

/**
 * The GDPR article 13 checks on the declared processing, shared by the packs of EU
 * member states. Cookie consent comes from the ePrivacy Directive, art. 5(3).
 */
trait GdprChecks
{
    /** @return list<string> */
    protected function gdprErrors(array $c): array
    {
        $errors = [];
        $privacy = $c['privacy'];

        if ($c['pages']['privacy']['enabled'] && [] === $privacy['processing']) {
            $errors[] = 'fineprint.privacy.processing is empty: declare what the site processes (server logs and contact emails count), or disable the privacy page.';
        }
        if ($privacy['host_is_processor'] && isset($privacy['processors']['host'])) {
            $errors[] = 'fineprint.privacy.processors: "host" is reserved for the host, which is listed on its own (set host_is_processor: false to declare it yourself).';
        }
        foreach ($privacy['processors'] as $id => $p) {
            if ($p['outside_eu'] && !static::filled($p['safeguards'])) {
                $errors[] = \sprintf('fineprint.privacy.processors.%s sends data outside the EEA: say under "safeguards" what frames the transfer (GDPR, art. 13.1.f).', $id);
            }
        }
        foreach ($privacy['processing'] as $i => $a) {
            foreach ($a['processors'] as $id) {
                if (!isset($privacy['processor_list'][$id])) {
                    $errors[] = \sprintf('fineprint.privacy.processing.%d names the processor "%s", which is not declared under fineprint.privacy.processors%s.', $i, $id, 'host' === $id ? ' (or set host.name)' : '');
                }
            }
            if ('legitimate_interests' === $a['legal_basis'] && !static::filled($a['legitimate_interest'])) {
                $errors[] = \sprintf('fineprint.privacy.processing.%d rests on legitimate interests: describe the interest under "legitimate_interest" (GDPR, art. 13.1.d).', $i);
            }
        }

        $nonEssential = array_filter($c['cookies']['list'], static fn (array $k) => 'essential' !== $k['type']);
        if ([] !== $nonEssential && !static::filled($c['cookies']['manage_url'])) {
            $errors[] = 'fineprint.cookies: non-essential cookies need consent that can be withdrawn at any time: set cookies.manage_url to the page where visitors change their choice.';
        }

        return $errors;
    }

    /** Rules on activities that hold in every EU member state. @return list<string> */
    protected function activityErrors(array $c): array
    {
        $errors = [];
        if (static::has($c, 'marketplace') && !static::filled($c['terms']['marketplace_ranking'])) {
            $errors[] = 'fineprint.activities includes marketplace: describe the main parameters that rank offers under terms.marketplace_ranking (Directive 2011/83/EU, art. 6a).';
        }
        if ($c['terms']['withdrawal_waiver'] && !(static::has($c, 'b2c') && (static::has($c, 'digital') || static::has($c, 'services')))) {
            $errors[] = 'fineprint.terms.withdrawal_waiver only applies to consumer sales of digital content or services: add b2c and digital or services to fineprint.activities, or turn it off.';
        }
        if ([] !== array_filter([$c['terms']['subscription_cancellation']]) && !static::has($c, 'subscription')) {
            $errors[] = 'fineprint.terms.subscription_cancellation is set but fineprint.activities does not include subscription.';
        }

        return $errors;
    }
}
