<?php
// ============================================================
//  CODE ARENA - Contest domain helpers
// ============================================================

function syncContestStatuses(PDO $pdo): void {
    $pdo->exec("UPDATE contests SET status = 'active' WHERE start_time <= NOW() AND end_time > NOW() AND status <> 'active'");
    $pdo->exec("UPDATE contests SET status = 'ended' WHERE end_time <= NOW() AND status <> 'ended'");
    $pdo->exec("UPDATE contests SET status = 'upcoming' WHERE start_time > NOW() AND status <> 'upcoming'");
}

function computedContestStatus(string $startTime, string $endTime): string {
    $now = time();
    $start = strtotime($startTime);
    $end = strtotime($endTime);
    if ($start === false || $end === false) return 'upcoming';
    if ($now < $start) return 'upcoming';
    if ($now < $end) return 'active';
    return 'ended';
}

function normalizeContestDateTime(string $value): ?string {
    $value = trim($value);
    if ($value === '') return null;
    $value = str_replace('T', ' ', $value);
    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value)) {
        $value .= ':00';
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value)) {
        return null;
    }
    return strtotime($value) === false ? null : $value;
}

function validateContestWindow(string $startInput, string $endInput): array {
    $start = normalizeContestDateTime($startInput);
    $end = normalizeContestDateTime($endInput);
    if (!$start || !$end) {
        err('Contest date/time must use YYYY-MM-DD HH:MM format', 422);
    }
    if (strtotime($start) >= strtotime($end)) {
        err('Contest end time must be after start time', 422);
    }
    return [$start, $end, computedContestStatus($start, $end)];
}
