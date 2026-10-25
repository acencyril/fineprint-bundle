<?php

declare(strict_types=1);

namespace Acencyril\FineprintBundle\Tests;

use Acencyril\FineprintBundle\Assembly\Validator;
use Acencyril\FineprintBundle\Pack\Eu\EuPack;
use Acencyril\FineprintBundle\Pack\Fr\FrPack;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/** Flex clears the cache right after installing: the recipe's configuration must pass its pack. */
final class RecipeTest extends TestCase
{
    private const DIR = __DIR__.'/../recipe';

    private static function assertValid(array $yaml, string $where): void
    {
        $config = ConfigurationTest::process($yaml['fineprint']);
        $pack = ['fr' => new FrPack(), 'eu' => new EuPack()][$config['jurisdiction']];
        self::assertSame([], Validator::errors($pack, $config), $where);
    }

    public function testTheRecipeConfigurationIsValid(): void
    {
        self::assertValid(Yaml::parseFile(self::DIR.'/config/packages/fineprint.yaml'), 'recipe');
    }

    public function testTheReadmeExamplesAreValid(): void
    {
        foreach (['README.md', 'README.fr.md'] as $file) {
            preg_match_all('/```yaml\n((?:#[^\n]*\n)*fineprint:\n.*?)```/s', file_get_contents(__DIR__.'/../'.$file), $m);
            $blocks = array_filter($m[1], static fn (string $b) => str_contains($b, '    site:'));
            self::assertNotEmpty($blocks, $file);
            foreach ($blocks as $i => $block) {
                self::assertValid(Yaml::parse($block), $file.' example '.$i);
            }
        }
    }

    public function testTheManifestRegistersTheBundleAndCopiesTheConfig(): void
    {
        $manifest = json_decode(file_get_contents(self::DIR.'/manifest.json'), true, 512, \JSON_THROW_ON_ERROR);

        self::assertSame(['all'], $manifest['bundles']['Acencyril\\FineprintBundle\\FineprintBundle']);
        self::assertSame('%CONFIG_DIR%/', $manifest['copy-from-recipe']['config/']);
        self::assertSame(['fineprint' => ['resource' => '.', 'type' => 'fineprint']], Yaml::parseFile(self::DIR.'/config/routes/fineprint.yaml'));
    }
}
