<?php
declare(strict_types=1);

/**
 * Hospital Archive Helper
 * Manages structured Year > Admin historical records, CSV exports,
 * and pure-PHP ZIP archiving for turnover records and administrative exports.
 */

/**
 * Return all hospital administrators (both active and deactivated) from the accounts table.
 */
function archive_get_admins(): array
{
    $pdo = db();
    $stmt = $pdo->query('SELECT * FROM accounts WHERE role = "Admin" ORDER BY id ASC');
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    foreach ($admins as &$a) {
        $a['full_name'] = function_exists('db_format_account_name')
            ? db_format_account_name($a, $pdo)
            : trim(($a['first_name'] ?? '') . (!empty($a['middle_name']) ? ' ' . $a['middle_name'] : '') . ' ' . ($a['last_name'] ?? ''));
    }
    unset($a);
    if (!$admins) {
        $admins = [[
            'id'          => 1,
            'user_id'     => 'A-0001',
            'first_name'  => 'Medea',
            'middle_name' => null,
            'last_name'   => 'Solon',
            'full_name'   => 'Medea Solon',
            'email'       => 'solonmedea582@gmail.com',
            'role'        => 'Admin',
            'status'      => 'Active',
        ]];
    }
    return $admins;
}

/**
 * Check if a date string falls within an optional [from, to] range.
 */
function archive_date_in_range(string $dateStr, ?string $from, ?string $to): bool
{
    if (empty($from) && empty($to)) {
        return true;
    }
    $t = strtotime($dateStr);
    if ($t === false) {
        return true;
    }
    $d = date('Y-m-d', $t);
    if (!empty($from) && $d < $from) {
        return false;
    }
    if (!empty($to) && $d > $to) {
        return false;
    }
    return true;
}

/**
 * Generate CSV text for a specific category.
 */
function archive_generate_csv(string $category, array $state, ?string $q = null, ?string $from = null, ?string $to = null): string
{
    $out = fopen('php://temp', 'r+');
    $qLower = !empty($q) ? strtolower(trim($q)) : null;

    switch (strtolower(str_replace([' ', '_', '-'], '', $category))) {
        case 'patients':
            fputcsv($out, ['MRN', 'Full Name', 'Birthdate', 'Sex', 'Phone', 'Email', 'Address', 'Status']);
            foreach ($state['patients'] ?? [] as $p) {
                $mrn  = patient_mrn_display($p) ?: ($p[8] ?? '—');
                $name = $p[0] ?? '';
                $dob  = format_birthdate($p[1] ?? '');
                $sex  = $p[5] ?? '—';
                $ph   = $p[2] ?? '—';
                $em   = $p['email'] ?? ($p[6] ?? '—');
                $addr = $p[7] ?? '—';
                $st   = $p[3] ?? 'Active';

                if ($qLower !== null) {
                    $blob = strtolower($mrn . ' ' . $name . ' ' . $ph . ' ' . $em . ' ' . $addr);
                    if (!str_contains($blob, $qLower)) {
                        continue;
                    }
                }
                fputcsv($out, [$mrn, $name, $dob, $sex, $ph, $em, $addr, $st]);
            }
            break;

        case 'billing':
            fputcsv($out, ['Invoice Ref', 'Patient Name', 'Service Category / Items', 'Total Amount (PHP)', 'Balance (PHP)', 'Status', 'Date Issued']);
            foreach ($state['bills'] ?? [] as $idx => $b) {
                $norm = function_exists('normalize_bill_row') ? normalize_bill_row($b, $idx) : $b;
                $ref  = (string) ($norm[4] ?? ($b['bill_no'] ?? ($b['id'] ?? 'BILL-1001')));
                $pt   = (string) ($norm[0] ?? ($b['patient'] ?? 'Patient'));
                $amt  = (float) ($norm[1] ?? ($b['total'] ?? 0));
                $st   = (string) ($norm[2] ?? ($b['status'] ?? 'Unpaid'));
                $dt   = (string) ($norm[3] ?? ($b['issued'] ?? date('Y-m-d')));
                $bal  = (float) ($norm['balance'] ?? ($b['balance'] ?? 0));

                $itemSummary = 'Hospital Services';
                if (!empty($norm[6]) && is_array($norm[6])) {
                    $itemNames = [];
                    foreach ($norm[6] as $it) {
                        $itemNames[] = is_array($it) ? ($it['name'] ?? ($it[0] ?? 'Service')) : (string) $it;
                    }
                    $itemSummary = implode('; ', $itemNames);
                }

                if (!archive_date_in_range($dt, $from, $to)) {
                    continue;
                }
                if ($qLower !== null) {
                    $blob = strtolower($ref . ' ' . $pt . ' ' . $itemSummary . ' ' . $st);
                    if (!str_contains($blob, $qLower)) {
                        continue;
                    }
                }
                fputcsv($out, [$ref, $pt, $itemSummary, number_format($amt, 2), number_format($bal, 2), $st, $dt]);
            }
            break;

        case 'appointments':
            fputcsv($out, ['Appt Code', 'Patient Name', 'Doctor', 'Department', 'Date', 'Time', 'Type', 'Status', 'Reason']);
            foreach ($state['appointments'] ?? [] as $ap) {
                $code = $ap['code'] ?? ($ap[7] ?? 'APT-1001');
                $pt   = $ap[1] ?? '';
                $doc  = $ap[2] ?? '';
                $dept = $ap[3] ?? '';
                $dt   = $ap[4] ?? date('Y-m-d');
                $time = $ap[5] ?? '';
                $type = $ap[6] ?? 'Consultation';
                $st   = $ap[0] ?? 'Scheduled';
                $rs   = $ap['reason'] ?? ($ap[9] ?? '');

                if (!archive_date_in_range((string) $dt, $from, $to)) {
                    continue;
                }
                if ($qLower !== null) {
                    $blob = strtolower($code . ' ' . $pt . ' ' . $doc . ' ' . $dept . ' ' . $st . ' ' . $rs);
                    if (!str_contains($blob, $qLower)) {
                        continue;
                    }
                }
                fputcsv($out, [$code, $pt, $doc, $dept, $dt, $time, $type, $st, $rs]);
            }
            break;

        case 'admissions':
            fputcsv($out, ['Bed ID', 'Room No', 'Bed Code', 'Ward', 'Room Type', 'Floor', 'Daily Rate (PHP)', 'Status', 'Patient', 'MRN', 'Admitted Date', 'Doctor']);
            foreach ($state['beds'] ?? [] as $bd) {
                $bid   = $bd['bed_id'] ?? '';
                $rNo   = $bd['room_no'] ?? '';
                $code  = $bd['bed_code'] ?? '';
                $ward  = $bd['ward'] ?? '';
                $rtype = $bd['room_type'] ?? '';
                $floor = $bd['floor'] ?? '';
                $rate  = (float) ($bd['rate'] ?? 0);
                $st    = $bd['status'] ?? 'Vacant';
                $pt    = $bd['patient'] ?? '—';
                $mrn   = $bd['mrn'] ?? '—';
                $adDt  = $bd['admitted_date'] ?? '—';
                $doc   = $bd['doctor'] ?? '—';

                if ($adDt !== '—' && !archive_date_in_range((string) $adDt, $from, $to)) {
                    continue;
                }
                if ($qLower !== null) {
                    $blob = strtolower($bid . ' ' . $rNo . ' ' . $code . ' ' . $ward . ' ' . $pt . ' ' . $mrn . ' ' . $doc);
                    if (!str_contains($blob, $qLower)) {
                        continue;
                    }
                }
                fputcsv($out, [$bid, $rNo, $code, $ward, $rtype, $floor, number_format($rate, 2), $st, $pt, $mrn, $adDt, $doc]);
            }
            break;

        case 'medicalrecords':
            fputcsv($out, ['Patient Name', 'Record Date', 'Attending Doctor', 'Department', 'Chief Complaint', 'Diagnosis', 'Treatment Plan']);
            foreach ($state['records'] ?? [] as $ptName => $recs) {
                if (is_array($recs)) {
                    foreach ($recs as $r) {
                        $dt   = $r['date'] ?? ($r[0] ?? date('Y-m-d'));
                        $doc  = $r['doctor'] ?? ($r[1] ?? 'Attending Physician');
                        $dept = $r['department'] ?? 'Clinical';
                        $cc   = $r['complaint'] ?? ($r[2] ?? '—');
                        $dx   = $r['diagnosis'] ?? ($r[3] ?? '—');
                        $tx   = $r['treatment'] ?? ($r[4] ?? '—');

                        if (!archive_date_in_range((string) $dt, $from, $to)) {
                            continue;
                        }
                        if ($qLower !== null) {
                            $blob = strtolower($ptName . ' ' . $doc . ' ' . $dept . ' ' . $cc . ' ' . $dx . ' ' . $tx);
                            if (!str_contains($blob, $qLower)) {
                                continue;
                            }
                        }
                        fputcsv($out, [$ptName, $dt, $doc, $dept, $cc, $dx, $tx]);
                    }
                }
            }
            break;

        case 'auditlogs':
            fputcsv($out, ['ID', 'Timestamp', 'Action', 'Module', 'Record Ref', 'Performed By', 'Role', 'IP Address']);
            foreach ($state['audit'] ?? [] as $a) {
                $id   = $a['id'] ?? ($a[0] ?? '');
                $ts   = $a['created_at'] ?? ($a[1] ?? date('Y-m-d H:i:s'));
                $act  = $a['action'] ?? ($a[2] ?? '');
                $mod  = $a['module'] ?? ($a[3] ?? '');
                $ref  = $a['record_id'] ?? ($a[4] ?? '');
                $by   = $a['user_name'] ?? ($a[5] ?? 'System');
                $urol = $a['role'] ?? ($a[6] ?? 'Admin');
                $ip   = $a['ip'] ?? '127.0.0.1';

                if (!archive_date_in_range((string) $ts, $from, $to)) {
                    continue;
                }
                if ($qLower !== null) {
                    $blob = strtolower($act . ' ' . $mod . ' ' . $ref . ' ' . $by . ' ' . $urol);
                    if (!str_contains($blob, $qLower)) {
                        continue;
                    }
                }
                fputcsv($out, [$id, $ts, $act, $mod, $ref, $by, $urol, $ip]);
            }
            break;

        default:
            fputcsv($out, ['Reference', 'Date', 'Description', 'Archived By']);
            foreach ($state['archive'] ?? [] as $ar) {
                fputcsv($out, [$ar[0] ?? '', $ar[3] ?? '', $ar[1] ?? '', $ar[4] ?? '']);
            }
            break;
    }

    rewind($out);
    $csv = stream_get_contents($out);
    fclose($out);
    return (string) $csv;
}

/**
 * Pure-PHP standard PKZIP builder that outputs a compliant ZIP stream in memory.
 * Does not require PECL zip or php_zip.dll.
 */
function archive_build_pkzip(array $files): string
{
    $data   = '';
    $cdir   = '';
    $offset = 0;

    foreach ($files as $name => $content) {
        $cleanName = str_replace('\\', '/', $name);
        $time      = time();
        $year      = max(1980, (int) date('Y', $time));
        $dtime     = (($year - 1980) << 25)
            | ((int) date('m', $time) << 21)
            | ((int) date('d', $time) << 16)
            | ((int) date('H', $time) << 11)
            | ((int) date('i', $time) << 5)
            | ((int) date('s', $time) >> 1);
        $hexdtime  = pack('V', $dtime);

        $uncLen    = strlen($content);
        $crc       = crc32($content);

        // Compress with raw deflate if zlib is enabled, else store uncompressed (method 0)
        if (function_exists('gzdeflate')) {
            $zdata    = (string) gzdeflate($content);
            $cLen     = strlen($zdata);
            $method   = "\x08\x00"; // Deflated
        } else {
            $zdata    = $content;
            $cLen     = $uncLen;
            $method   = "\x00\x00"; // Stored
        }

        // Local file header
        $fr = "\x50\x4b\x03\x04\x14\x00\x00\x00" . $method . $hexdtime
            . pack('VVVvv', $crc, $cLen, $uncLen, strlen($cleanName), 0)
            . $cleanName . $zdata;

        $data .= $fr;

        // Central directory entry
        $cdrec = "\x50\x4b\x01\x02\x00\x00\x14\x00\x00\x00" . $method . $hexdtime
            . pack('VVVvvvvvVV', $crc, $cLen, $uncLen, strlen($cleanName), 0, 0, 0, 0, 32, $offset)
            . $cleanName;

        $cdir   .= $cdrec;
        $offset += strlen($fr);
    }

    // End of central directory record
    $count = count($files);
    $eof   = "\x50\x4b\x05\x06\x00\x00\x00\x00"
        . pack('vvVVv', $count, $count, strlen($cdir), strlen($data), 0);

    return $data . $cdir . $eof;
}
