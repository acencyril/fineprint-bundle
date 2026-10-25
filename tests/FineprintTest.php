<?php

declare(strict_types=1);

namespace Acencyril\FineprintBundle\Tests;

use Acencyril\FineprintBundle\Fineprint;
use PHPUnit\Framework\TestCase;

final class FineprintTest extends TestCase
{
    private function fineprint(): Fineprint
    {
        return new Fineprint(['locales' => ['fr', 'en'], 'pages' => [
            'legal_notice' => ['enabled' => true, 'path' => ['fr' => '/mentions/', 'en' => 'notice']],
            'terms' => ['enabled' => false],
            'privacy' => ['enabled' => true],
            'cookies' => ['enabled' => false],
        ]]);
    }

    public function testEnabledPagesKeepTheirOrder(): void
    {
        self::assertSame(['legal_notice', 'privacy'], $this->fineprint()->enabledPages());
    }

    public function testPathsAreUnderTheLocale(): void
    {
        self::assertSame('/fr/mentions', $this->fineprint()->path('legal_notice', 'fr'));
        self::assertSame('/en/notice', $this->fineprint()->path('legal_notice', 'en'));
    }

    public function testLocaleResolution(): void
    {
        $f = $this->fineprint();
        self::assertSame('en', $f->resolveLocale('en'));
        self::assertSame('fr', $f->resolveLocale('fr_BE'));
        self::assertSame('fr', $f->resolveLocale('de'));
        self::assertSame('fr', $f->resolveLocale(null));
    }

    public function testLocalizedTexts(): void
    {
        $f = $this->fineprint();
        self::assertSame('same', $f->text('same', 'en'));
        self::assertNull($f->text(null, 'en'));
        self::assertSame('anglais', $f->text(['fr' => 'français', 'en' => 'anglais'], 'en'));
        self::assertSame('français', $f->text(['fr' => 'français', 'en' => 'anglais'], 'fr_CA'));
        self::assertSame('français', $f->text(['fr' => 'français'], 'de'), 'falls back to the first configured locale');
        self::assertSame('Deutsch', $f->text(['de' => 'Deutsch'], 'en'), 'then to the first entry');
    }
}
