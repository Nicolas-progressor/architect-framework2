<?php

declare(strict_types=1);

namespace Architect\Services\I18n;

use Architect\Contracts\ServiceProviderInterface;
use Architect\Core\Contracts\ContainerInterface;
use Architect\Services\I18n\Contracts\LanguageDetectorInterface;
use Architect\Services\I18n\Contracts\LanguageInterface;
use Architect\Services\I18n\Contracts\TranslationLoaderInterface;

/**
 * Service provider for I18n services.
 */
final class LanguageServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerInterface $container): void
    {
        // LanguageConfig
        $container->factory(LanguageConfig::class, function () {
            return LanguageConfig::default();
        });

        // LanguageDetector
        $container->factory(LanguageDetectorInterface::class, function () use ($container) {
            $config = $container->get(LanguageConfig::class);
            return new LanguageDetector($config->getDefaultLanguage());
        });

        // TranslationLoader
        $container->factory(TranslationLoaderInterface::class, function () use ($container) {
            $config = $container->get(LanguageConfig::class);
            return new FileTranslationLoader($config->getBasePath());
        });

        // Language service (main)
        $container->factory(Language::class, function () use ($container) {
            return new Language(
                $container,
                $container->get(LanguageDetectorInterface::class),
                $container->get(TranslationLoaderInterface::class),
                $container->get(LanguageConfig::class)
            );
        });

        // Alias for backward compatibility
        $container->factory('language', function () use ($container) {
            return $container->get(Language::class);
        });

        // Also bind LanguageInterface to Language
        $container->factory(LanguageInterface::class, function () use ($container) {
            return $container->get(Language::class);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $container): void
    {
        // No boot actions required for Language services
    }
}
