<?php

declare(strict_types=1);

namespace Acencyril\FineprintBundle\Tests;

use Acencyril\FineprintBundle\Tests\Fixtures\TestKernel;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\KernelInterface;

abstract class KernelTestCase extends WebTestCase
{
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new TestKernel($options['environment'] ?? 'test', true, $options['fineprint'] ?? []);
    }

    protected static function browse(array $fineprint = [], string $environment = 'test'): KernelBrowser
    {
        self::ensureKernelShutdown();

        return static::createClient(['environment' => $environment, 'fineprint' => $fineprint]);
    }
}
