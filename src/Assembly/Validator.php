<?php

declare(strict_types=1);

namespace Acencyril\FineprintBundle\Assembly;

use Acencyril\FineprintBundle\Fineprint;
use Acencyril\FineprintBundle\Pack\AbstractPack;
use Acencyril\FineprintBundle\Pack\PackInterface;

/**
 * Everything checked once the pack is known: its required fields, its own rules, and
 * the page sections against its clauses. Run at build time by RegisterPacksPass.
 */
final class Validator
{
    /**
     * @param array $config prepared configuration (Fineprint::prepare())
     *
     * @return list<string>
     */
    public static function errors(PackInterface $pack, array $config): array
    {
        $name = $pack->name();
        $errors = [];

        foreach ($pack->clauses() as $clause) {
            if ([] !== $clause->packs && !\in_array($name, $clause->packs, true)) {
                $errors[] = \sprintf('Pack "%s" returns the clause "%s.%s", which declares the packs [%s] only.', $name, $clause->page, $clause->id, implode(', ', $clause->packs));
            }
        }

        foreach ($pack->requiredFields($config) as $path) {
            if (!AbstractPack::filled(AbstractPack::get($config, $path))) {
                $errors[] = \sprintf('fineprint.%s is required by the "%s" pack.', $path, $name);
            }
        }

        array_push($errors, ...$pack->validate($config));

        foreach (Fineprint::PAGES as $page) {
            $sections = $config['pages'][$page]['sections'];
            $ids = [];
            foreach ($pack->clauses() as $clause) {
                if ($clause->page === $page) {
                    $ids[] = $clause->id;
                }
            }
            foreach ($sections['remove'] as $id) {
                if (!\in_array($id, $ids, true)) {
                    $errors[] = \sprintf('fineprint.pages.%s.sections.remove: the "%s" pack has no clause "%s" on this page (it has: %s).', $page, $name, $id, implode(', ', $ids));
                }
            }
            foreach (array_keys($sections['override']) as $id) {
                if (!\in_array($id, $ids, true)) {
                    $errors[] = \sprintf('fineprint.pages.%s.sections.override: the "%s" pack has no clause "%s" on this page (it has: %s).', $page, $name, $id, implode(', ', $ids));
                }
            }
            $added = [];
            foreach ($sections['add'] as $a) {
                if (\in_array($a['id'], $ids, true) || \in_array($a['id'], $added, true)) {
                    $errors[] = \sprintf('fineprint.pages.%s.sections.add: the id "%s" is already taken; use override to replace a clause.', $page, $a['id']);
                }
                $added[] = $a['id'];
            }
            foreach ($sections['add'] as $a) {
                if (str_contains($a['position'], ':') && !\in_array($anchor = explode(':', $a['position'])[1], [...$ids, ...$added], true)) {
                    $errors[] = \sprintf('fineprint.pages.%s.sections.add.%s: position "%s" names no clause or added section.', $page, $a['id'], $a['position']);
                }
            }

            if ($config['pages'][$page]['enabled'] && [] === Assembler::plan($pack, $config, $page)) {
                $errors[] = \sprintf('fineprint.pages.%s: the "%s" pack has nothing to show on this page for these activities; add sections or disable the page.', $page, $name);
            }
        }

        return $errors;
    }
}
