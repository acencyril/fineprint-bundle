<?php

declare(strict_types=1);

namespace Acencyril\FineprintBundle\Controller;

use Acencyril\FineprintBundle\Fineprint;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;

final class PageController
{
    public function __construct(private readonly Fineprint $fineprint, private readonly Environment $twig)
    {
    }

    public function __invoke(Request $request, string $page): Response
    {
        if (!$this->fineprint->isEnabled($page)) {
            throw new NotFoundHttpException();
        }
        $config = $this->fineprint->config();
        $locale = $this->fineprint->resolveLocale($request->getLocale());

        $response = new Response($this->twig->render('@Fineprint/'.$page.'.html.twig', [
            'page' => $page,
            'locale' => $locale,
            'fineprint' => $config,
            'site' => $config['site'],
            'publisher' => $config['publisher'],
            'host' => $config['host'],
            'mediator' => $config['mediator']['enabled'] ? $config['mediator'] : null,
            'parent_template' => $config['parent_template'],
        ]));
        $response->headers->set('Content-Language', $locale);

        return $response;
    }
}
