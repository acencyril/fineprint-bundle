<?php

declare(strict_types=1);

use Acencyril\FineprintBundle\Controller\PageController;
use Acencyril\FineprintBundle\Fineprint;
use Acencyril\FineprintBundle\Routing\FineprintRouteLoader;
use Acencyril\FineprintBundle\Sitemap\FineprintUrls;
use Acencyril\FineprintBundle\Twig\FineprintTwigExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $c): void {
    $services = $c->services()->defaults()->autoconfigure(false)->autowire(false);

    // The processed configuration is set by FineprintExtension.
    $services->set(Fineprint::class)->args([[]]);

    $services->set(FineprintRouteLoader::class)
        ->args([service(Fineprint::class)])
        ->tag('routing.loader');

    /* Dependencies injected explicitly, no AbstractController: autoconfiguration is off
       here and its service locator would never be built. */
    $services->set(PageController::class)
        ->args([service(Fineprint::class), service('twig')])
        ->public();

    $services->set(FineprintTwigExtension::class)
        ->args([service(Fineprint::class), service('router'), service('translator'), service('request_stack')])
        ->tag('twig.extension');

    $services->set(FineprintUrls::class)
        ->args([service(Fineprint::class)]);
    $services->alias('fineprint.urls', FineprintUrls::class)->public();
};
