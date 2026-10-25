<?php

declare(strict_types=1);

namespace Acencyril\FineprintBundle\Tests\Fixtures;

use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final class FooterController
{
    public function __construct(private readonly Environment $twig)
    {
    }

    public function __invoke(): Response
    {
        return new Response($this->twig->createTemplate("{{ include('@Fineprint/footer_links.html.twig') }}")->render());
    }
}
