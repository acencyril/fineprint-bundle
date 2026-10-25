<?php

declare(strict_types=1);

namespace Acencyril\FineprintBundle\Tests;

use Acencyril\FineprintBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;

/** Flex clears the cache right after installing: the recipe's configuration must be valid. */
final class RecipeTest extends TestCase
{
    private const DIR = __DIR__.'/../recipe';

    public function testTheRecipeConfigurationIsValid(): void
    {
        $yaml = Yaml::parseFile(self::DIR.'/config/packages/fineprint.yaml');
        $config = (new Processor())->processConfiguration(new Configuration(), [$yaml['fineprint']]);

        self::assertSame('b2b', $config['terms']['audience']);
        self::assertStringContainsString('CHANGE ME', $config['publisher']['legal_name']);
    }

    public function testTheReadmeExamplesAreValid(): void
    {
        foreach (['README.md', 'README.fr.md'] as $file) {
            preg_match_all('/```yaml\n((?:#[^\n]*\n)*fineprint:\n.*?)```/s', file_get_contents(__DIR__.'/../'.$file), $m);
            $blocks = array_filter($m[1], static fn (string $b) => str_contains($b, '    site:'));
            self::assertNotEmpty($blocks, $file);
            foreach ($blocks as $block) {
                $config = (new Processor())->processConfiguration(new Configuration(), [Yaml::parse($block)['fineprint']]);
                self::assertNotEmpty($config['privacy']['activities'], $file);
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
