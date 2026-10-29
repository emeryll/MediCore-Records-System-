<?php
declare(strict_types=1);

/* ---------------------------------------------------------------
 * Booking helpers: doctor schedules, slot availability, upcoming list
 * ------------------------------------------------------------- */

/** Statuses of an appointment that can still be rescheduled or cancelled. */
const BOOKABLE_STATUSES = ['Confirmed', 'Waiting', 'Upcoming'];

function doctor_by_name(string $name): ?array
{
    foreach (doctors_data() as $d) {
        if (strcasecmp($d[0], $name) === 0) {
            return $d;
        }
    }
    return null;
}

/** "Mon – Fri" / "Mon, Wed, Fri" -> ISO weekday numbers (1 = Mon … 7 = Sun). */
function parse_workdays(string $text): array
{
    $map  = ['mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4, 'fri' => 5, 'sat' => 6, 'sun' => 7];
    $days = [];
    if (preg_match('/^(\w{3})\s*[–-]\s*(\w{3})$/u', trim($text), $m)) {
        $from = $map[strtolower($m[1])] ?? 1;
        $to   = $map[strtolower($m[2])] ?? 5;
        for ($d = $from; ; $d = $d % 7 + 1) {
            $days[] = $d;
            if ($d === $to) {
                break;
            }
        }
        return $days;
    }
    foreach (preg_split('/\s*,\s*/', trim($text)) ?: [] as $part) {
        $key = strtolower(substr($part, 0, 3));
        if (isset($map[$key])) {
            $days[] = $map[$key];
        }
    }
    return $days;
}

/** Half-hour slots, 9:00 – 16:30, with a lunch break at 12:00. */
function booking_slots(): array
{
    return ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30'];
}

function slot_label(string $slot): string
{
    return date('g:i A', strtotime($slot) ?: 0);
}

function normalize_slot(string $time): string
{
    $ts = strtotime($time);
    return $ts === false ? $time : date('H:i', $ts);
}

/** Earliest and latest bookable dates (Y-m-d). */
function booking_window(): array
{
    return [date('Y-m-d', strtotime('+1 day')), date('Y-m-d', strtotime('+60 days'))];
}

/** Does this patient already have an active appointment (any doctor) at this exact date and time? */
function patient_has_slot(array $state, string $patient, string $ymd, string $slot, int $ignoreIdx = -1): bool
{
    if ($patient === '') {
        return false;
    }
    foreach ($state['appointments'] as $i => $a) {
        if ($i === $ignoreIdx || ($a[3] ?? '') === 'Cancelled' || ($a[1] ?? '') !== $patient) {
            continue;
        }
        if (date('Y-m-d', strtotime((string) ($a[7] ?? 'today')) ?: 0) === $ymd
            && normalize_slot((string) $a[0]) === $slot) {
            return true;
        }
    }
    return false;
}

/** Is this exact slot taken (by a real appointment or the patient's own visit)? */
function slot_booked(array $state, string $doctor, string $ymd, string $slot, int $ignoreIdx = -1, string $patient = ''): bool
{
    if (patient_has_slot($state, $patient, $ymd, $slot, $ignoreIdx)) {
        return true;
    }
    foreach ($state['appointments'] as $i => $a) {
        if ($i === $ignoreIdx || ($a[3] ?? '') === 'Cancelled') {
            continue;
        }
        if (($a[5] ?? '') === $doctor
            && date('Y-m-d', strtotime((string) ($a[7] ?? 'today')) ?: 0) === $ymd
            && normalize_slot((string) $a[0]) === $slot) {
            return true;
        }
    }
    return false;
}

/**
 * Status of a calendar day for a doctor.
 * Returns ['status' => 'past'|'off'|'full'|'open', 'free' => int]
 */
function day_availability(array $state, array $doctor, string $ymd, int $ignoreIdx = -1, string $patient = ''): array
{
    [$min, $max] = booking_window();
    if ($ymd < $min || $ymd > $max) {
        return ['status' => 'past', 'free' => 0];
    }
    $weekday = (int) date('N', strtotime($ymd));
    if (!in_array($weekday, parse_workdays($doctor[4]), true)) {
        return ['status' => 'off', 'free' => 0];
    }
    $free = 0;
    foreach (booking_slots() as $s) {
        if (!slot_booked($state, $doctor[0], $ymd, $s, $ignoreIdx, $patient)) {
            $free++;
        }
    }
    return ['status' => $free > 0 ? 'open' : 'full', 'free' => $free];
}

/** Can this slot be booked right now? */
function slot_available(array $state, array $doctor, string $ymd, string $slot, int $ignoreIdx = -1, string $patient = ''): bool
{
    if (!in_array($slot, booking_slots(), true)) {
        return false;
    }
    if (day_availability($state, $doctor, $ymd, $ignoreIdx, $patient)['status'] !== 'open') {
        return false;
    }
    return !slot_booked($state, $doctor[0], $ymd, $slot, $ignoreIdx, $patient);
}

/** Gradient avatar standing in for a doctor's photo. */
function doctor_photo(string $name, string $class = ''): string
{
    $palette = [['#0f766e', '#14b8a6'], ['#1e3a5f', '#3b82f6'], ['#7c3aed', '#a78bfa'], ['#b45309', '#f59e0b'], ['#be123c', '#fb7185']];
    [$a, $b] = $palette[crc32($name) % count($palette)];
    return '<span class="bk-photo ' . e($class) . '" style="background:linear-gradient(135deg,' . $a . ',' . $b . ')">'
        . e(initials(str_replace('Dr. ', '', $name), 2)) . '</span>';
}

/**
 * "Upcoming appointments" list with Reschedule and Cancel actions.
 * Patients see their own; staff see everyone's.
 */
function upcoming_appointments_html(array $state, string $role): string
{
    $me   = current_user_name('Patient');
    $rows = [];
    foreach ($state['appointments'] as $idx => $a) {
        if (!in_array($a[3], BOOKABLE_STATUSES, true)) {
            continue;
        }
        if ($role === 'Patient' && $a[1] !== $me) {
            continue;
        }
        $rows[$idx] = $a;
    }
    uasort($rows, static fn ($x, $y) => strtotime($x[7] . ' ' . $x[0]) <=> strtotime($y[7] . ' ' . $y[0]));

    ob_start();
    ?>
    <section class="panel bk-upcoming">
        <div class="panel-head">
            <div>
                <h2>Upcoming appointments</h2>
                <p><?= count($rows) ?> scheduled visit<?= count($rows) !== 1 ? 's' : '' ?> you can reschedule or cancel</p>
            </div>
        </div>
        <div class="bk-upcoming-list">
            <?php foreach ($rows as $idx => $a): ?>
                <article class="bk-upcoming-row">
                    <div class="pt-appt-date-box">
                        <span><?= e(strtoupper(date('M', strtotime($a[7])))) ?></span>
                        <strong><?= e(date('d', strtotime($a[7]))) ?></strong>
                        <small><?= e(slot_label(normalize_slot((string) $a[0]))) ?></small>
                    </div>
                    <div class="bk-upcoming-info">
                        <strong><?= e($a[5]) ?> <span class="pt-dept-tag"><?= e($a[6]) ?></span></strong>
                        <?php if ($role !== 'Patient'): ?><p>Patient: <b><?= e($a[1]) ?></b></p><?php endif; ?>
                        <p><?= e($a[4] !== '' ? $a[4] : $a[2]) ?></p>
                    </div>
                    <?= badge($a[3], status_tone($a[3])) ?>
                    <div class="bk-upcoming-actions">
                        <?= button('Reschedule', ['variant' => 'secondary', 'icon' => 'calendar', 'href' => url(['page' => 'book', 'step' => 2, 'reschedule' => $idx])]) ?>
                        <?= post_button('Cancel', 'cancel_appt', ['idx' => (string) $idx], ['variant' => 'secondary', 'class' => 'btn-cancel']) ?>
                    </div>
                </article>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
                <div class="empty-state" style="padding:28px 20px;">
                    <?= icon('calendar', 26) ?>
                    <strong>No upcoming appointments</strong>
                    <p>Visits you book will appear here.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>
    <?php
    return (string) ob_get_clean();
}
