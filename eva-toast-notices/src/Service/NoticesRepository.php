<?php

declare(strict_types=1);

namespace EvaToastNotices\Service;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * In-memory storage for normalized notices during a single request.
 */
final class NoticesRepository
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private array $notices = [];

    /**
     * Add a normalized notice.
     *
     * @param string $context     admin|frontend
     * @param string $source      core|plugin|woocommerce|theme|unknown
     * @param string $type        success|error|warning|info
     * @param string $message     Sanitized html string.
     * @param bool   $dismissible Whether notice can be dismissed.
     */
    public function addNotice(
        string $context,
        string $source,
        string $type,
        string $message,
        bool $dismissible = true
    ): void {
        $this->notices[] = [
            'context'     => $context,
            'source'      => $source,
            'type'        => $type,
            'message'     => $message,
            'dismissible' => $dismissible,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getNotices(): array
    {
        return $this->notices;
    }

    public function hasNotices(): bool
    {
        return $this->notices !== [];
    }

    public function clear(): void
    {
        $this->notices = [];
    }
}


