<?php

declare(strict_types=1);

namespace Acencyril\FineprintBundle\Routing;

use Acencyril\FineprintBundle\Controller\PageController;
use Acencyril\FineprintBundle\Fineprint;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Builds the routes from the configuration (resource ".", type "fineprint").
 *
 * A loader rather than a routes file, because both the paths and the set of pages come
 * from the configuration: a disabled page has no route at all, so it answers 404 like
 * any unknown URL instead of an empty page.
 *
 * Each page is a localized route: "fineprint_terms.fr", "fineprint_terms.en", sharing the
 * canonical name "fineprint_terms", which the router resolves from the current locale.
 */
final class FineprintRouteLoader extends Loader
{
    private bool $loaded = false;

    public function __construct(private readonly Fineprint $fineprint)
    {
        parent::__construct();
    }

    public function load(mixed $resource, ?string $type = null): RouteCollection
    {
        if ($this->loaded) {
            throw new \RuntimeException('The "fineprint" routes are imported twice.');
        }
        $this->loaded = true;

        $routes = new RouteCollection();
        foreach ($this->fineprint->enabledPages() as $page) {
            $name = $this->fineprint->routeName($page);
            foreach ($this->fineprint->locales() as $locale) {
                $routes->add($name.'.'.$locale, (new Route($this->fineprint->path($page, $locale)))
                    ->setDefaults([
                        '_controller' => PageController::class,
                        '_locale' => $locale,
                        '_canonical_route' => $name,
                        'page' => $page,
                    ])
                    ->setRequirement('_locale', preg_quote($locale))
                    ->setMethods(['GET', 'HEAD']));
            }
        }

        return $routes;
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return 'fineprint' === $type;
    }
}
