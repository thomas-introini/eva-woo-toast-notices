<?php

declare(strict_types=1);

namespace EvaToastNotices\Frontend;

use EvaToastNotices\Admin\SettingsPage;
use EvaToastNotices\Service\NoticesRepository;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Intercepts WooCommerce notices on the frontend and converts them to toasts.
 */
final class WooNoticesInterceptor
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
        add_action('init', [$this, 'hookWooNotices'], 99);
    }

    public function hookWooNotices(): void
    {
        if (is_admin()) {
            return;
        }

        if (! class_exists('\\WooCommerce')) {
            return;
        }

        $options = $this->settingsPage->getOptions();
        if (empty($options['enable_frontend']) || empty($options['enable_woo'])) {
            return;
        }

        // Remove default WooCommerce notice output from common hooks.
        $hooks = [
            'woocommerce_before_shop_loop',
            'woocommerce_before_single_product',
            'woocommerce_before_cart',
            'woocommerce_before_checkout_form',
            'woocommerce_before_account_navigation',
            'woocommerce_before_main_content',
        ];

        foreach ($hooks as $hook) {
            remove_action($hook, 'woocommerce_output_all_notices', 10);
            add_action($hook, [$this, 'captureWooNotices'], 1);
        }
    }

    /**
     * Capture and clear WooCommerce notices then store as normalized data.
     */
    public function captureWooNotices(): void
    {
        if (! function_exists('wc_get_notices')) {
            return;
        }

        $allNotices = wc_get_notices();

        if (! is_array($allNotices) || $allNotices === []) {
            return;
        }

        foreach ($allNotices as $type => $notices) {
            if (! is_array($notices)) {
                continue;
            }

            foreach ($notices as $notice) {
                if (! is_array($notice) || ! isset($notice['notice'])) {
                    continue;
                }

                $message = (string) $notice['notice'];

                // WooCommerce notice content is generally already escaped where needed.
                $allowedTags = [
                    'a'      => [
                        'href'   => true,
                        'title'  => true,
                        'target' => true,
                        'rel'    => true,
                    ],
                    'strong' => [],
                    'em'     => [],
                    'b'      => [],
                    'i'      => [],
                    'code'   => [],
                    'br'     => [],
                    'span'   => [
                        'class' => true,
                    ],
                    'p'      => [],
                    'ul'     => [],
                    'ol'     => [],
                    'li'     => [],
                ];

                $sanitized = wp_kses($message, $allowedTags);

                if (trim(wp_strip_all_tags($sanitized)) === '') {
                    continue;
                }

                $normalizedType = $this->mapWooType((string) $type);

                $this->repository->addNotice(
                    'frontend',
                    'woocommerce',
                    $normalizedType,
                    $sanitized,
                    true
                );
            }
        }

        if (function_exists('wc_clear_notices')) {
            wc_clear_notices();
        }
    }

    private function mapWooType(string $type): string
    {
        switch ($type) {
            case 'error':
                return 'error';
            case 'success':
                return 'success';
            case 'notice':
            default:
                return 'info';
        }
    }
}


