<?php

declare(strict_types=1);

namespace EvaToastNotices;

use EvaToastNotices\Admin\AdminNoticesInterceptor;
use EvaToastNotices\Admin\SettingsPage;
use EvaToastNotices\Assets\AssetsManager;
use EvaToastNotices\Frontend\WooNoticesInterceptor;
use EvaToastNotices\Service\NoticesRepository;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Main plugin orchestrator.
 */
final class Plugin
{
    private static ?Plugin $instance = null;

    private NoticesRepository $noticesRepository;

    private SettingsPage $settingsPage;

    private AdminNoticesInterceptor $adminNoticesInterceptor;

    private ?WooNoticesInterceptor $wooNoticesInterceptor = null;

    private AssetsManager $assetsManager;

    private function __construct()
    {
        $this->noticesRepository    = new NoticesRepository();
        $this->settingsPage         = new SettingsPage();
        $this->adminNoticesInterceptor = new AdminNoticesInterceptor(
            $this->noticesRepository,
            $this->settingsPage
        );

        if ($this->isWooCommerceActive()) {
            $this->wooNoticesInterceptor = new WooNoticesInterceptor(
                $this->noticesRepository,
                $this->settingsPage
            );
        }

        $this->assetsManager = new AssetsManager(
            $this->noticesRepository,
            $this->settingsPage
        );
    }

    public static function instance(): Plugin
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Initialize plugin pieces.
     */
    public function init(): void
    {
        $this->settingsPage->register();
        $this->adminNoticesInterceptor->register();

        if ($this->wooNoticesInterceptor !== null) {
            $this->wooNoticesInterceptor->register();
        }

        $this->assetsManager->register();
    }

    /**
     * Plugin activation handler.
     */
    public static function activate(): void
    {
        $defaultOptions = SettingsPage::get_default_options();
        $existing       = get_option(SettingsPage::OPTION_NAME);

        if (! is_array($existing)) {
            update_option(SettingsPage::OPTION_NAME, $defaultOptions);
        } else {
            update_option(SettingsPage::OPTION_NAME, array_merge($defaultOptions, $existing));
        }
    }

    private function isWooCommerceActive(): bool
    {
        return class_exists('\\WooCommerce');
    }
}


