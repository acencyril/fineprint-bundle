<?php

declare(strict_types=1);

namespace Acencyril\FineprintBundle\Pack;

/**
 * A jurisdiction: the clauses of the four pages, the fields its law requires and the
 * checks it runs on the configuration.
 *
 * Any service implementing this interface is a pack (autoconfigured, tag
 * "fineprint.pack"); the site picks one with fineprint.jurisdiction. A pack is
 * instantiated without arguments at build time, to validate the configuration before
 * any page is served: its constructor takes no dependencies.
 */
interface PackInterface
{
    /** The value of fineprint.jurisdiction that selects this pack, e.g. "fr". */
    public function name(): string;

    /** The translation domain of its texts, e.g. "fineprint_fr". */
    public function translationDomain(): string;

    /** @return list<Clause> in display order, all pages together */
    public function clauses(): array;

    /**
     * Configuration paths that must be filled for this site, e.g. "host.phone".
     *
     * @param array $config the processed configuration
     *
     * @return list<string>
     */
    public function requiredFields(array $config): array;

    /**
     * What else is wrong with the configuration, as messages naming the key and the rule.
     *
     * @return list<string>
     */
    public function validate(array $config): array;
}
