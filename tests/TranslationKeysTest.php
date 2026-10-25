<?php

declare(strict_types=1);

namespace Acencyril\FineprintBundle\Tests;

use Acencyril\FineprintBundle\Fineprint;
use Acencyril\FineprintBundle\Pack\Eu\EuPack;
use Acencyril\FineprintBundle\Pack\Fr\FrPack;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/** Each pack has every text it refers to, in French and in English, and the same keys in both. */
final class TranslationKeysTest extends TestCase
{
    private static function flatten(array $a, string $prefix = ''): array
    {
        $out = [];
        foreach ($a as $k => $v) {
            \is_array($v) ? $out += self::flatten($v, $prefix.$k.'.') : $out[$prefix.$k] = $v;
        }

        return $out;
    }

    public function testEveryClauseKeyExistsInBothLanguages(): void
    {
        foreach ([new FrPack(), new EuPack()] as $pack) {
            $keys = [];
            foreach (['fr', 'en'] as $locale) {
                $keys[$locale] = self::flatten(Yaml::parseFile(\sprintf('%s/../translations/%s.%s.yaml', __DIR__, $pack->translationDomain(), $locale)));
            }
            self::assertSame([], array_diff_key($keys['fr'], $keys['en']), $pack->name().': keys missing in English');
            self::assertSame([], array_diff_key($keys['en'], $keys['fr']), $pack->name().': keys missing in French');

            $needed = [];
            foreach ($pack->clauses() as $clause) {
                array_push($needed, ...array_filter([$clause->title, $clause->body]));
                if (null !== $clause->link) {
                    $needed[] = 'page.'.$clause->link.'.link_inline';
                }
            }
            foreach (Fineprint::PAGES as $page) {
                array_push($needed, "page.$page.title", "page.$page.link", "page.$page.description");
            }
            foreach (array_unique($needed) as $key) {
                self::assertArrayHasKey($key, $keys['fr'], $pack->name().': '.$key);
            }
        }
    }
}
