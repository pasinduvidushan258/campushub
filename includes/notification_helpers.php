<?php

if (!function_exists('timeAgo')) {
    function timeAgo($datetime): string {
        if (empty($datetime)) {
            return 'Just now';
        }

        try {
            $then = new DateTime((string) $datetime);
            $now = new DateTime();
        } catch (Exception $e) {
            return 'Just now';
        }

        $diff = $now->getTimestamp() - $then->getTimestamp();

        if ($diff < 60) {
            return 'Just now';
        }

        if ($diff < 3600) {
            $mins = (int) floor($diff / 60);
            return $mins . ' min' . ($mins === 1 ? '' : 's') . ' ago';
        }

        if ($diff < 86400) {
            $hours = (int) floor($diff / 3600);
            return $hours . ' hour' . ($hours === 1 ? '' : 's') . ' ago';
        }

        if ($diff < 604800) {
            $days = (int) floor($diff / 86400);
            return $days . ' day' . ($days === 1 ? '' : 's') . ' ago';
        }

        if ($diff < 2592000) {
            $weeks = (int) floor($diff / 604800);
            return $weeks . ' week' . ($weeks === 1 ? '' : 's') . ' ago';
        }

        if ($diff < 31536000) {
            $months = (int) floor($diff / 2592000);
            return $months . ' month' . ($months === 1 ? '' : 's') . ' ago';
        }

        $years = (int) floor($diff / 31536000);
        return $years . ' year' . ($years === 1 ? '' : 's') . ' ago';
    }
}
