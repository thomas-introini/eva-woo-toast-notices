<?php

declare(strict_types=1);

namespace EvaToastNotices\Assets;

use EvaToastNotices\Admin\SettingsPage;
use EvaToastNotices\Service\NoticesRepository;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Handles enqueueing JS/CSS assets and exposing notice data to JS.
 */
final class AssetsManager
{
    private NoticesRepository $repository;

    private SettingsPage $settingsPage;

    public function __construct(NoticesRepository $repository, SettingsPage $settingsPage)
    {
        $this->repository   = $repository;
        $this->settingsPage = $settingsPage;
    }

    public function register(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueFrontendAssets']);

        // Inject data after notices have been captured.
        add_action('admin_footer', [$this, 'outputInlineNoticesData']);
        add_action('wp_footer', [$this, 'outputInlineNoticesData']);
    }

    public function enqueueAdminAssets(): void
    {
        $options = $this->settingsPage->getOptions();
        if (empty($options['enable_admin'])) {
            return;
        }

        $this->enqueueCommonAssets();
    }

    public function enqueueFrontendAssets(): void
    {
        $options = $this->settingsPage->getOptions();
        if (empty($options['enable_frontend'])) {
            return;
        }

        // For simplicity we enqueue on all frontend pages when enabled.
        $this->enqueueCommonAssets();
    }

    /**
     * Enqueue Toastify (minimal implementation) and plugin JS/CSS.
     */
    private function enqueueCommonAssets(): void
    {
        $toastifyJs  = EVA_TOAST_NOTICES_PATH . 'assets/js/toastify.min.js';
        $toastifyCss = EVA_TOAST_NOTICES_PATH . 'assets/css/toastify.min.css';
        $mainJs      = EVA_TOAST_NOTICES_PATH . 'assets/js/eva-toast-notices.js';
        $mainCss     = EVA_TOAST_NOTICES_PATH . 'assets/css/eva-toast-notices.css';

        $toastifyJsUrl  = EVA_TOAST_NOTICES_URL . 'assets/js/toastify.min.js';
        $toastifyCssUrl = EVA_TOAST_NOTICES_URL . 'assets/css/toastify.min.css';
        $mainJsUrl      = EVA_TOAST_NOTICES_URL . 'assets/js/eva-toast-notices.js';
        $mainCssUrl     = EVA_TOAST_NOTICES_URL . 'assets/css/eva-toast-notices.css';

        $toastifyJsVer  = file_exists($toastifyJs) ? (string) filemtime($toastifyJs) : EVA_TOAST_NOTICES_VERSION;
        $toastifyCssVer = file_exists($toastifyCss) ? (string) filemtime($toastifyCss) : EVA_TOAST_NOTICES_VERSION;
        $mainJsVer      = file_exists($mainJs) ? (string) filemtime($mainJs) : EVA_TOAST_NOTICES_VERSION;
        $mainCssVer     = file_exists($mainCss) ? (string) filemtime($mainCss) : EVA_TOAST_NOTICES_VERSION;

        wp_enqueue_style(
            'eva-toast-notices-toastify',
            $toastifyCssUrl,
            [],
            $toastifyCssVer
        );

        wp_enqueue_style(
            'eva-toast-notices',
            $mainCssUrl,
            ['eva-toast-notices-toastify'],
            $mainCssVer
        );

        wp_enqueue_script(
            'eva-toast-notices-toastify',
            $toastifyJsUrl,
            [],
            $toastifyJsVer,
            true
        );

        wp_enqueue_script(
            'eva-toast-notices',
            $mainJsUrl,
            ['eva-toast-notices-toastify'],
            $mainJsVer,
            true
        );
    }

    /**
     * Output global JS variables with notices and settings data.
     */
    public function outputInlineNoticesData(): void
    {
        if (! wp_script_is('eva-toast-notices', 'enqueued')) {
            return;
        }

        $options = $this->settingsPage->getOptions();

        $data = [
            'notices'  => $this->repository->getNotices(),
            'settings' => [
                'position'          => (string) ($options['position'] ?? 'top-right'),
                'timeoutNonError'   => (int) ($options['timeout_non_error'] ?? 5000),
                'errorTimeout'      => (int) max((int) ($options['timeout_non_error'] ?? 5000), 8000),
            ],
        ];

        $json = wp_json_encode($data);

        if (! is_string($json)) {
            return;
        }

        ?>
        <script>
            window.EvaToastNoticesData = <?php echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
        </script>
        <?php
    }
}


