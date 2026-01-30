<?php

namespace Clicks\Logger;

class ClientDataMasker
{
    /**
     * Built-in patterns for sensitive data detection.
     *
     * @var array<string, array{pattern: string, replacement: string, visible_chars: int}>
     */
    protected const PATTERNS = [
        'credit_cards' => [
            'pattern' => '/\b(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|3[47][0-9]{13}|6(?:011|5[0-9]{2})[0-9]{12}|(?:2131|1800|35\d{3})\d{11})\b/',
            'replacement' => '[CREDIT_CARD]',
            'visible_chars' => 4,
        ],
        'ssn' => [
            'pattern' => '/\b\d{3}-\d{2}-\d{4}\b/',
            'replacement' => '[SSN]',
            'visible_chars' => 4,
        ],
        'api_keys' => [
            'pattern' => '/\b(?:sk_(?:live|test)_[a-zA-Z0-9]{24,}|pk_(?:live|test)_[a-zA-Z0-9]{24,}|Bearer\s+[a-zA-Z0-9._\-]{20,}|api[_-]?key["\'\s:=]+["\']?[a-zA-Z0-9_\-]{20,}["\']?)/i',
            'replacement' => '[API_KEY]',
            'visible_chars' => 0,
        ],
        'passwords' => [
            'pattern' => '/(?:password|passwd|pwd|secret|token|credential)["\'\s:=]+["\']?[^\s"\'<>,;]{4,}["\']?/i',
            'replacement' => '[PASSWORD]',
            'visible_chars' => 0,
        ],
        'emails' => [
            'pattern' => '/\b[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Z|a-z]{2,}\b/',
            'replacement' => '[EMAIL]',
            'visible_chars' => 0,
        ],
        'phone_numbers' => [
            'pattern' => '/(?<![0-9])(?:\+?1[-.\s]?)?(?:\([2-9]\d{2}\)\s?|[2-9]\d{2}[-.\s]?)\d{3}[-.\s]?\d{4}(?![0-9])/',
            'replacement' => '[PHONE]',
            'visible_chars' => 4,
        ],
        'ip_addresses' => [
            'pattern' => '/\b(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\b/',
            'replacement' => '[IP_ADDRESS]',
            'visible_chars' => 0,
        ],
    ];

    /**
     * Keys that indicate sensitive values (case-insensitive).
     */
    protected const SENSITIVE_KEYS = [
        'password', 'passwd', 'pwd', 'secret', 'token', 'api_key', 'apikey',
        'api-key', 'access_token', 'auth_token', 'authorization', 'bearer',
        'credential', 'credentials', 'private_key', 'privatekey', 'private-key',
    ];

    protected bool $enabled;

    protected string $style;

    /** @var array<string> */
    protected array $enabledPatterns;

    /** @var array<array{name: string, pattern: string, replacement: string}> */
    protected array $customPatterns;

    protected int $maxDepth;

    public function __construct(?array $config = null)
    {
        $config = $config ?? config('clicks-logger.masking', []);

        $this->enabled = $config['enabled'] ?? true;
        $this->style = $config['style'] ?? 'full';
        $this->enabledPatterns = $config['patterns'] ?? [
            'credit_cards',
            'ssn',
            'api_keys',
            'passwords',
            'phone_numbers',
        ];
        $this->customPatterns = $config['custom_patterns'] ?? [];
        $this->maxDepth = $config['max_depth'] ?? 10;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Mask sensitive data in a log record array.
     *
     * @return array{message: string, context: array<mixed>, extra: array<mixed>}
     */
    public function maskRecord(array $record): array
    {
        if (! $this->enabled) {
            return $record;
        }

        if (isset($record['message'])) {
            $record['message'] = $this->maskString($record['message']);
        }

        if (isset($record['context']) && is_array($record['context'])) {
            $record['context'] = $this->maskArray($record['context']);
        }

        if (isset($record['extra']) && is_array($record['extra'])) {
            $record['extra'] = $this->maskArray($record['extra']);
        }

        return $record;
    }

    /**
     * Mask sensitive data in a string.
     */
    public function maskString(string $value): string
    {
        // Apply built-in patterns
        foreach ($this->enabledPatterns as $patternKey) {
            if (! isset(self::PATTERNS[$patternKey])) {
                continue;
            }

            $patternConfig = self::PATTERNS[$patternKey];
            $value = $this->applyPattern(
                $value,
                $patternConfig['pattern'],
                $patternConfig['replacement'],
                $patternConfig['visible_chars']
            );
        }

        // Apply custom patterns
        foreach ($this->customPatterns as $customPattern) {
            if (empty($customPattern['pattern'])) {
                continue;
            }

            $value = $this->applyPattern(
                $value,
                $customPattern['pattern'],
                $customPattern['replacement'] ?? '[REDACTED]',
                0
            );
        }

        return $value;
    }

    /**
     * Recursively mask sensitive data in an array.
     *
     * @param  array<mixed>  $data
     * @return array<mixed>
     */
    public function maskArray(array $data, int $depth = 0): array
    {
        if ($depth >= $this->maxDepth) {
            return $data;
        }

        $result = [];

        foreach ($data as $key => $value) {
            // Check if this key indicates a sensitive value
            if (is_string($key) && $this->isSensitiveKey($key)) {
                $result[$key] = $this->maskSensitiveValue($value);

                continue;
            }

            if (is_string($value)) {
                $result[$key] = $this->maskString($value);
            } elseif (is_array($value)) {
                $result[$key] = $this->maskArray($value, $depth + 1);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Apply a regex pattern with the configured masking style.
     */
    protected function applyPattern(string $value, string $pattern, string $replacement, int $visibleChars): string
    {
        return preg_replace_callback($pattern, function ($matches) use ($replacement, $visibleChars) {
            return $this->maskValue($matches[0], $replacement, $visibleChars);
        }, $value) ?? $value;
    }

    /**
     * Mask a matched value based on the configured style.
     */
    protected function maskValue(string $value, string $replacement, int $visibleChars): string
    {
        return match ($this->style) {
            'partial' => $this->partialMask($value, $replacement, $visibleChars),
            'hash' => $this->hashMask($value),
            default => $replacement, // 'full' style
        };
    }

    /**
     * Create a partial mask showing last N characters.
     */
    protected function partialMask(string $value, string $replacement, int $visibleChars): string
    {
        if ($visibleChars === 0 || strlen($value) <= $visibleChars) {
            return $replacement;
        }

        $suffix = substr($value, -$visibleChars);

        return '****'.$suffix;
    }

    /**
     * Create a hash-based mask.
     */
    protected function hashMask(string $value): string
    {
        $hash = substr(hash('sha256', $value), 0, 8);

        return '['.$hash.']';
    }

    /**
     * Check if a key name indicates a sensitive value.
     */
    protected function isSensitiveKey(string $key): bool
    {
        $normalizedKey = strtolower(str_replace(['-', '_'], '', $key));

        foreach (self::SENSITIVE_KEYS as $sensitiveKey) {
            $normalizedSensitive = strtolower(str_replace(['-', '_'], '', $sensitiveKey));
            if (str_contains($normalizedKey, $normalizedSensitive)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mask a value that was identified by a sensitive key.
     */
    protected function maskSensitiveValue(mixed $value): string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return '[REDACTED]';
        }

        $stringValue = (string) $value;

        return match ($this->style) {
            'partial' => $this->partialMask($stringValue, '[REDACTED]', 4),
            'hash' => $this->hashMask($stringValue),
            default => '[REDACTED]',
        };
    }
}
