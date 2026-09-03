<?php

declare(strict_types=1);

namespace App\Validators;

final class ReminderValidator
{
    private const DISPLAY_FORMATS = ['dhms', 'hm', 'm', 's'];
    private const PRIORITIES = ['low', 'normal', 'high', 'critical'];
    private const RECURRENCE_TYPES = ['none', 'daily', 'weekly', 'monthly', 'yearly', 'custom'];

    /** @return string[] field => message */
    public static function validateCreate(array $data): array
    {
        $errors = [];

        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '' || mb_strlen($title) > 150) {
            $errors['title'] = 'Title is required and must be under 150 characters.';
        }

        if (empty($data['target_datetime']) || !self::isValidDateTime($data['target_datetime'])) {
            $errors['target_datetime'] = 'A valid target date and time is required.';
        }

        if (!empty($data['timezone']) && !in_array($data['timezone'], timezone_identifiers_list(), true)) {
            $errors['timezone'] = 'Unrecognized timezone.';
        }

        if (!empty($data['display_format']) && !in_array($data['display_format'], self::DISPLAY_FORMATS, true)) {
            $errors['display_format'] = 'Invalid countdown format.';
        }

        if (!empty($data['priority']) && !in_array($data['priority'], self::PRIORITIES, true)) {
            $errors['priority'] = 'Invalid priority.';
        }

        if (!empty($data['recurrence_type']) && !in_array($data['recurrence_type'], self::RECURRENCE_TYPES, true)) {
            $errors['recurrence_type'] = 'Invalid recurrence type.';
        }

        foreach (['text_color', 'background_color', 'accent_color'] as $colorField) {
            if (!empty($data[$colorField]) && !self::isValidHexColor($data[$colorField])) {
                $errors[$colorField] = 'Must be a valid hex color, e.g. #F2A93B.';
            }
        }

        if (!empty($data['description']) && mb_strlen($data['description']) > 2000) {
            $errors['description'] = 'Description must be under 2000 characters.';
        }

        return $errors;
    }

    private static function isValidDateTime(string $value): bool
    {
        $dt = \DateTime::createFromFormat('Y-m-d H:i:s', $value)
            ?: \DateTime::createFromFormat(\DateTime::ATOM, $value);
        return $dt !== false;
    }

    private static function isValidHexColor(string $value): bool
    {
        return (bool) preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value);
    }
}
