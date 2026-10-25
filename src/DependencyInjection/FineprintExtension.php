<?php

declare(strict_types=1);

namespace Acencyril\FineprintBundle\DependencyInjection;

use Acencyril\FineprintBundle\Fineprint;
use Acencyril\FineprintBundle\Pack\PackInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

final class FineprintExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = Fineprint::prepare($this->processConfiguration(new Configuration(), $configs));

        // Any PackInterface service of the application or of another bundle is a pack.
        $container->registerForAutoconfiguration(PackInterface::class)->addTag('fineprint.pack');

        (new PhpFileLoader($container, new FileLocator(\dirname(__DIR__, 2).'/config')))->load('services.php');

        /* Escaped: a legal text easily holds a "%" ("20 % VAT"), which the container would
           otherwise read as a parameter reference. */
        $container->getDefinition(Fineprint::class)
            ->setArgument(0, $container->getParameterBag()->escapeValue($config));
    }

    public function getAlias(): string
    {
        return 'fineprint';
    }
}
