<?php

declare(strict_types=1);

namespace EvaToastNotices\Admin;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Settings page under Settings → Eva Toast Notices.
 */
final class SettingsPage
{
    public const OPTION_NAME = 'eva_toast_notices_options';

    public static function get_default_options(): array
    {
        return [
            'enable_admin'      => true,
            'enable_frontend'   => true,
            'enable_woo'        => true,
            'position'          => 'top-right',
            'timeout_non_error' => 5000,
        ];
    }

    public function register(): void
    {
        add_action('admin_menu', [$this, 'addSettingsPage']);
        add_action('admin_init', [$this, 'registerSettings']);
    }

    public function addSettingsPage(): void
    {
        add_options_page(
            esc_html__('Eva Toast Notices', 'eva-toast-notices'),
            esc_html__('Eva Toast Notices', 'eva-toast-notices'),
            'manage_options',
            'eva-toast-notices',
            [$this, 'renderPage']
        );
    }

    public function registerSettings(): void
    {
        register_setting(
            'eva_toast_notices',
            self::OPTION_NAME,
            [
                'type'              => 'array',
                'sanitize_callback' => [$this, 'sanitizeOptions'],
                'default'           => self::get_default_options(),
            ]
        );

        add_settings_section(
            'eva_toast_notices_main',
            esc_html__('Toast Settings', 'eva-toast-notices'),
            static function (): void {
                echo '<p>' . esc_html__(
                    'Configure how toast notifications behave in admin and frontend.',
                    'eva-toast-notices'
                ) . '</p>';
            },
            'eva-toast-notices'
        );

        add_settings_field(
            'enable_admin',
            esc_html__('Enable in admin', 'eva-toast-notices'),
            [$this, 'renderCheckboxField'],
            'eva-toast-notices',
            'eva_toast_notices_main',
            [
                'label_for' => 'enable_admin',
                'option_key' => 'enable_admin',
            ]
        );

        add_settings_field(
            'enable_frontend',
            esc_html__('Enable on frontend', 'eva-toast-notices'),
            [$this, 'renderCheckboxField'],
            'eva-toast-notices',
            'eva_toast_notices_main',
            [
                'label_for'  => 'enable_frontend',
                'option_key' => 'enable_frontend',
            ]
        );

        if ($this->isWooCommerceActive()) {
            add_settings_field(
                'enable_woo',
                esc_html__('Override WooCommerce notices', 'eva-toast-notices'),
                [$this, 'renderCheckboxField'],
                'eva-toast-notices',
                'eva_toast_notices_main',
                [
                    'label_for'  => 'enable_woo',
                    'option_key' => 'enable_woo',
                ]
            );
        }

        add_settings_field(
            'position',
            esc_html__('Toast position', 'eva-toast-notices'),
            [$this, 'renderPositionField'],
            'eva-toast-notices',
            'eva_toast_notices_main',
            [
                'label_for'  => 'position',
                'option_key' => 'position',
            ]
        );

        add_settings_field(
            'timeout_non_error',
            esc_html__('Timeout for non-error notices (ms)', 'eva-toast-notices'),
            [$this, 'renderTimeoutField'],
            'eva-toast-notices',
            'eva_toast_notices_main',
            [
                'label_for'  => 'timeout_non_error',
                'option_key' => 'timeout_non_error',
            ]
        );
    }

    /**
     * Sanitize and validate options.
     *
     * @param array<string, mixed> $options Raw input.
     *
     * @return array<string, mixed>
     */
    public function sanitizeOptions(array $options): array
    {
        $defaults = self::get_default_options();

        $sanitized = [
            'enable_admin'      => ! empty($options['enable_admin']),
            'enable_frontend'   => ! empty($options['enable_frontend']),
            'enable_woo'        => ! empty($options['enable_woo']),
            'position'          => $defaults['position'],
            'timeout_non_error' => $defaults['timeout_non_error'],
        ];

        if (isset($options['position']) && is_string($options['position'])) {
            $allowedPositions = ['top-right', 'top-left', 'bottom-right', 'bottom-left'];
            if (in_array($options['position'], $allowedPositions, true)) {
                $sanitized['position'] = $options['position'];
            }
        }

        if (isset($options['timeout_non_error'])) {
            $timeout = (int) $options['timeout_non_error'];
            if ($timeout < 1000) {
                $timeout = 1000;
            }

            if ($timeout > 30000) {
                $timeout = 30000;
            }

            $sanitized['timeout_non_error'] = $timeout;
        }

        if (! $this->isWooCommerceActive()) {
            $sanitized['enable_woo'] = false;
        }

        return $sanitized;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        $saved    = get_option(self::OPTION_NAME, []);
        $defaults = self::get_default_options();

        if (! is_array($saved)) {
            $saved = [];
        }

        return array_merge($defaults, $saved);
    }

    /**
     * Render main settings page.
     */
    public function renderPage(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $options = $this->getOptions();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Eva Toast Notices', 'eva-toast-notices'); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('eva_toast_notices');
                do_settings_sections('eva-toast-notices');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render a checkbox field.
     *
     * @param array<string, string> $args Arguments from add_settings_field.
     */
    public function renderCheckboxField(array $args): void
    {
        $options   = $this->getOptions();
        $optionKey = $args['option_key'] ?? '';
        $value     = ! empty($options[$optionKey]);
        ?>
        <label for="<?php echo esc_attr($optionKey); ?>">
            <input
                type="checkbox"
                id="<?php echo esc_attr($optionKey); ?>"
                name="<?php echo esc_attr(self::OPTION_NAME . '[' . $optionKey . ']'); ?>"
                value="1"
                <?php checked($value); ?>
            />
            <?php esc_html_e('Enabled', 'eva-toast-notices'); ?>
        </label>
        <?php
    }

    /**
     * Render toast position select field.
     *
     * @param array<string, string> $args Arguments from add_settings_field.
     */
    public function renderPositionField(array $args): void
    {
        $options   = $this->getOptions();
        $optionKey = $args['option_key'] ?? 'position';
        $value     = is_string($options[$optionKey] ?? '') ? $options[$optionKey] : 'top-right';

        $positions = [
            'top-right'    => esc_html__('Top right', 'eva-toast-notices'),
            'top-left'     => esc_html__('Top left', 'eva-toast-notices'),
            'bottom-right' => esc_html__('Bottom right', 'eva-toast-notices'),
            'bottom-left'  => esc_html__('Bottom left', 'eva-toast-notices'),
        ];
        ?>
        <select
            id="<?php echo esc_attr($optionKey); ?>"
            name="<?php echo esc_attr(self::OPTION_NAME . '[' . $optionKey . ']'); ?>"
        >
            <?php foreach ($positions as $posValue => $label) : ?>
                <option
                    value="<?php echo esc_attr($posValue); ?>"
                    <?php selected($value, $posValue); ?>
                >
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Render timeout field.
     *
     * @param array<string, string> $args Arguments from add_settings_field.
     */
    public function renderTimeoutField(array $args): void
    {
        $options   = $this->getOptions();
        $optionKey = $args['option_key'] ?? 'timeout_non_error';
        $value     = isset($options[$optionKey]) ? (int) $options[$optionKey] : 5000;
        ?>
        <input
            type="number"
            min="1000"
            max="30000"
            step="500"
            id="<?php echo esc_attr($optionKey); ?>"
            name="<?php echo esc_attr(self::OPTION_NAME . '[' . $optionKey . ']'); ?>"
            value="<?php echo esc_attr((string) $value); ?>"
        />
        <?php
    }

    private function isWooCommerceActive(): bool
    {
        return class_exists('\\WooCommerce');
    }
}


