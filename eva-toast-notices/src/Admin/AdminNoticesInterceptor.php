<?php

declare(strict_types=1);

namespace EvaToastNotices\Admin;

use DOMDocument;
use DOMXPath;
use EvaToastNotices\Service\NoticesRepository;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Captures default admin notices and converts them into normalized toast data.
 */
final class AdminNoticesInterceptor
{
    private NoticesRepository $repository;

    private SettingsPage $settingsPage;

    /**
     * Track active buffers keyed by hook name.
     *
     * @var array<string, int>
     */
    private array $buffers = [];

    public function __construct(NoticesRepository $repository, SettingsPage $settingsPage)
    {
        $this->repository   = $repository;
        $this->settingsPage = $settingsPage;
    }

    public function register(): void
    {
        add_action('admin_init', [$this, 'maybeHookAdminNotices']);
    }

    /**
     * Hook into admin notices only when enabled.
     */
    public function maybeHookAdminNotices(): void
    {
        if (! is_admin()) {
            return;
        }

        $options = $this->settingsPage->getOptions();
        if (empty($options['enable_admin'])) {
            return;
        }

        $hooks = [
            'admin_notices',
            'all_admin_notices',
            'network_admin_notices',
        ];

        foreach ($hooks as $hook) {
            add_action($hook, [$this, 'startBuffer'], 0);
            add_action($hook, [$this, 'captureBuffer'], PHP_INT_MAX);
        }
    }

    /**
     * Start output buffering for notices.
     */
    public function startBuffer(): void
    {
        $hook = current_filter();

        if (isset($this->buffers[$hook])) {
            return;
        }

        $this->buffers[$hook] = 1;
        ob_start();
    }

    /**
     * Capture buffered HTML, parse notices, and prevent original output.
     */
    public function captureBuffer(): void
    {
        $hook = current_filter();

        if (! isset($this->buffers[$hook])) {
            return;
        }

        unset($this->buffers[$hook]);
        $html = (string) ob_get_clean();
        if ($html === '') {
            return;
        }

        $this->parseAndStoreNotices($html);

        // Do NOT echo original HTML; we fully replace via JS toasts.
    }

    /**
     * Parse HTML fragments and extract notice elements.
     */
    private function parseAndStoreNotices(string $html): void
    {
        if (trim($html) === '') {
            return;
        }

        $wrappedHtml = '<!DOCTYPE html><html><body><div id="eva-toast-root">' . $html . '</div></body></html>';

        $document = new DOMDocument('1.0', 'UTF-8');

        $previous = libxml_use_internal_errors(true);

        try {
            $document->loadHTML(
                $wrappedHtml,
                LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET | LIBXML_NOCDATA
            );
        } catch (\Exception $e) {
            // If DOM parsing fails, fall back to original notices rendering.
            libxml_clear_errors();
            libxml_use_internal_errors($previous);

            echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

            return;
        }

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new DOMXPath($document);

        $query = '//*[contains(concat(" ", normalize-space(@class), " "), " notice ")
                    or contains(concat(" ", normalize-space(@class), " "), " update-nag ")
                    or contains(concat(" ", normalize-space(@class), " "), " error ")
                    or contains(concat(" ", normalize-space(@class), " "), " updated ")]';

        $nodes = $xpath->query($query);

        if (! $nodes) {
            // If no notices found, fall back to original output.
            echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

            return;
        }

        foreach ($nodes as $node) {
            $classAttr = $node->getAttribute('class');
            $classes   = preg_split('/\s+/', (string) $classAttr) ?: [];

            $type = $this->mapTypeFromClasses($classes);

            $dismissible = in_array('is-dismissible', $classes, true);

            $innerHtml = '';
            foreach ($node->childNodes as $child) {
                $innerHtml .= $document->saveHTML($child);
            }

            $innerHtml = (string) $innerHtml;

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

            $sanitized = wp_kses($innerHtml, $allowedTags);

            if (trim(wp_strip_all_tags($sanitized)) === '') {
                continue;
            }

            $this->repository->addNotice(
                'admin',
                'plugin',
                $type,
                $sanitized,
                $dismissible
            );
        }
    }

    /**
     * Map WP notice CSS classes to normalized type.
     *
     * @param array<int, string> $classes
     */
    private function mapTypeFromClasses(array $classes): string
    {
        $classes = array_map('strtolower', $classes);

        if (in_array('notice-success', $classes, true) || in_array('updated', $classes, true)) {
            return 'success';
        }

        if (in_array('notice-error', $classes, true) || in_array('error', $classes, true)) {
            return 'error';
        }

        if (in_array('notice-warning', $classes, true) || in_array('update-nag', $classes, true)) {
            return 'warning';
        }

        if (in_array('notice-info', $classes, true)) {
            return 'info';
        }

        // Default to info for unclassified notices.
        return 'info';
    }
}


