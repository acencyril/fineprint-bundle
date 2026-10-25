<?php

declare(strict_types=1);

namespace Acencyril\FineprintBundle\DependencyInjection\Compiler;

use Acencyril\FineprintBundle\Assembly\Validator;
use Acencyril\FineprintBundle\Fineprint;
use Acencyril\FineprintBundle\Pack\AbstractPack;
use Acencyril\FineprintBundle\Pack\PackInterface;
use Acencyril\FineprintBundle\Pack\PackRegistry;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\EnvPlaceholderParameterBag;

/**
 * Finds the packs (services tagged "fineprint.pack"), hands their classes to the
 * registry, and validates the configuration against the chosen one: a page that breaks
 * its jurisdiction's rules stops the build instead of reaching a visitor.
 */
final class RegisterPacksPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(Fineprint::class)) {
            return;
        }

        $classes = [];
        foreach (array_keys($container->findTaggedServiceIds('fineprint.pack')) as $id) {
            $class = $container->getParameterBag()->resolveValue($container->getDefinition($id)->getClass() ?? $id);
            $pack = $container->getReflectionClass($class)?->newInstance();
            if (!$pack instanceof PackInterface) {
                throw new InvalidConfigurationException(\sprintf('The service "%s" is tagged "fineprint.pack" but does not implement %s.', $id, PackInterface::class));
            }
            if (isset($classes[$pack->name()]) && $classes[$pack->name()] !== $class) {
                throw new InvalidConfigurationException(\sprintf('Two Fineprint packs are named "%s": %s and %s.', $pack->name(), $classes[$pack->name()], $class));
            }
            $classes[$pack->name()] = $class;
        }
        $container->getDefinition(PackRegistry::class)->setArgument(0, $classes);

        $bag = $container->getParameterBag();
        $config = $bag->unescapeValue($container->getDefinition(Fineprint::class)->getArgument(0));
        $config = $this->markEnv($config, $bag instanceof EnvPlaceholderParameterBag ? array_merge([], ...array_values($bag->getEnvPlaceholders())) : []);

        $name = $config['jurisdiction'];
        if (!isset($classes[$name])) {
            throw new InvalidConfigurationException(\sprintf('fineprint.jurisdiction: no pack named "%s" (available: %s).', $name, implode(', ', array_keys($classes))));
        }
        $errors = Validator::errors(new $classes[$name](), $config);
        if ([] !== $errors) {
            throw new InvalidConfigurationException("Invalid Fineprint configuration:\n- ".implode("\n- ", $errors));
        }
    }

    /** Values that come from %env()% are unknown now: packs see them as DYNAMIC. */
    private function markEnv(mixed $value, array $placeholders): mixed
    {
        if (\is_array($value)) {
            return array_map(fn ($v) => $this->markEnv($v, $placeholders), $value);
        }
        if (\is_string($value)) {
            foreach ($placeholders as $placeholder) {
                if (str_contains($value, $placeholder)) {
                    return AbstractPack::DYNAMIC;
                }
            }
        }

        return $value;
    }
}
