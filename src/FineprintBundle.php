<?php

declare(strict_types=1);

namespace Acencyril\FineprintBundle;

use Acencyril\FineprintBundle\DependencyInjection\Compiler\RegisterPacksPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Fineprint — the legal pages of a Symfony site: legal notice, terms of sale, privacy
 * policy and cookies, in French and English.
 *
 * `Bundle` rather than `AbstractBundle`, as in Sentinelle: the latter carries its own
 * configuration and ignores an extension class written alongside it.
 */
class FineprintBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new RegisterPacksPass());
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
