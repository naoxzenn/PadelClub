<?php
// helpers/OperatingHoursHelper.php

class OperatingHoursHelper
{
    private static array $dayNames = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu'
    ];

    /**
     * Get day name in Bahasa Indonesia (1=Senin ... 7=Minggu)
     */
    public static function getDayName(int $dayOfWeek): string
    {
        return self::$dayNames[$dayOfWeek] ?? 'Hari ' . $dayOfWeek;
    }

    /**
     * Fetch operating hours for a specific date (Y-m-d)
     * @param string $date (Y-m-d)
     * @param PDO|mysqli|null $dbConnection
     * @return array
     */
    public static function getOperatingHoursByDate($date, $dbConnection = null): array
    {
        if (empty($dbConnection)) {
            global $pdo, $conn;
            $dbConnection = $pdo ?? $conn;
        }

        $timestamp = strtotime((string)$date);
        if ($timestamp === false) {
            $timestamp = time();
        }

        // 1 (Monday/Senin) to 7 (Sunday/Minggu)
        $dayOfWeek = (int)date('N', $timestamp);
        $dayName   = self::getDayName($dayOfWeek);

        // Default fallback schedule if record not found
        $defaultOpen   = ($dayOfWeek >= 6) ? '06:00:00' : '07:00:00';
        $defaultClose  = ($dayOfWeek >= 6) ? '23:00:00' : '22:00:00';
        $defaultIsOpen = 1;

        $record = null;

        if ($dbConnection instanceof PDO) {
            try {
                $stmt = $dbConnection->prepare("SELECT * FROM operating_hours WHERE day_of_week = ? LIMIT 1");
                $stmt->execute([$dayOfWeek]);
                $record = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (\Throwable $e) {
                error_log("OperatingHoursHelper PDO Error: " . $e->getMessage());
            }
        } elseif ($dbConnection) {
            $res = mysqli_query($dbConnection, "SELECT * FROM operating_hours WHERE day_of_week = $dayOfWeek LIMIT 1");
            if ($res && mysqli_num_rows($res) > 0) {
                $record = mysqli_fetch_assoc($res);
            }
        }

        if ($record) {
            $open2  = (!empty($record['open_time_2']) && $record['open_time_2'] !== '00:00:00') ? $record['open_time_2'] : null;
            $close2 = (!empty($record['close_time_2']) && $record['close_time_2'] !== '00:00:00') ? $record['close_time_2'] : null;
            $hasShift2 = (!empty($open2) && !empty($close2));

            return [
                'day_of_week'  => $dayOfWeek,
                'day_name'     => $dayName,
                'is_open'      => (int)$record['is_open'],
                'open_time'    => $record['open_time'],
                'close_time'   => $record['close_time'],
                'open_time_2'  => $open2,
                'close_time_2' => $close2,
                'has_shift2'   => $hasShift2
            ];
        }

        return [
            'day_of_week'  => $dayOfWeek,
            'day_name'     => $dayName,
            'is_open'      => $defaultIsOpen,
            'open_time'    => $defaultOpen,
            'close_time'   => $defaultClose,
            'open_time_2'  => null,
            'close_time_2' => null,
            'has_shift2'   => false
        ];
    }

    /**
     * Validate whether booking start and end times fall within open_time and close_time (supports 2 shifts / break time)
     * @param string $date (Y-m-d)
     * @param string $jamMulai (HH:MM / HH:MM:SS)
     * @param string $jamSelesai (HH:MM / HH:MM:SS)
     * @param PDO|mysqli|null $dbConnection
     * @return array ['valid' => bool, 'message' => string]
     */
    public static function validateBookingTimes($date, $jamMulai, $jamSelesai, $dbConnection = null): array
    {
        $op = self::getOperatingHoursByDate($date, $dbConnection);

        if (!$op['is_open']) {
            return [
                'valid'   => false,
                'message' => 'Booking tidak dapat dilakukan. Venue tutup pada hari tersebut.'
            ];
        }

        $baseDate   = '1970-01-01 ';
        $tMulai     = strtotime($baseDate . $jamMulai);
        $tSelesai   = strtotime($baseDate . $jamSelesai);

        $tOpen1     = strtotime($baseDate . $op['open_time']);
        $tClose1    = strtotime($baseDate . $op['close_time']);

        if ($tMulai === false || $tSelesai === false || $tOpen1 === false || $tClose1 === false) {
            return [
                'valid'   => false,
                'message' => 'Format jam booking tidak valid.'
            ];
        }

        // Shift 1 Check
        $validShift1 = ($tMulai >= $tOpen1 && $tSelesai <= $tClose1);

        // Shift 2 Check
        $validShift2 = false;
        if ($op['has_shift2']) {
            $tOpen2  = strtotime($baseDate . $op['open_time_2']);
            $tClose2 = strtotime($baseDate . $op['close_time_2']);
            if ($tOpen2 !== false && $tClose2 !== false) {
                $validShift2 = ($tMulai >= $tOpen2 && $tSelesai <= $tClose2);
            }
        }

        if ($validShift1 || $validShift2) {
            return [
                'valid'   => true,
                'message' => ''
            ];
        }

        // Generate error message according to shifts
        $fmtOpen1  = date('H.i', $tOpen1);
        $fmtClose1 = date('H.i', $tClose1);

        if ($op['has_shift2']) {
            $tOpen2    = strtotime($baseDate . $op['open_time_2']);
            $tClose2   = strtotime($baseDate . $op['close_time_2']);
            $fmtOpen2  = date('H.i', $tOpen2);
            $fmtClose2 = date('H.i', $tClose2);

            return [
                'valid'   => false,
                'message' => "Booking tidak dapat dilakukan. Jam operasional hari {$op['day_name']} adalah {$fmtOpen1} - {$fmtClose1} & {$fmtOpen2} - {$fmtClose2} WIB."
            ];
        }

        return [
            'valid'   => false,
            'message' => "Booking tidak dapat dilakukan. Jam operasional hari {$op['day_name']} adalah {$fmtOpen1} - {$fmtClose1} WIB."
        ];
    }

    /**
     * Fetch entire 7-day schedule
     */
    public static function getWeeklySchedule($dbConnection = null): array
    {
        $schedule = [];
        for ($i = 1; $i <= 7; $i++) {
            $date = date('Y-m-d', strtotime("this Monday +" . ($i - 1) . " days"));
            $schedule[$i] = self::getOperatingHoursByDate($date, $dbConnection);
        }
        return $schedule;
    }

    /**
     * Get formatted schedule string for display (e.g., Landing Page)
     */
    public static function getFormattedScheduleSummary($dbConnection = null): string
    {
        $schedule = self::getWeeklySchedule($dbConnection);

        $weekdayIsOpen = $schedule[1]['is_open'] ?? 1;
        $weekendIsOpen = $schedule[6]['is_open'] ?? 1;

        $parts = [];

        if ($weekdayIsOpen) {
            $fmtWOpen1  = date('H.i', strtotime("1970-01-01 " . $schedule[1]['open_time']));
            $fmtWClose1 = date('H.i', strtotime("1970-01-01 " . $schedule[1]['close_time']));
            $str = "Senin – Jumat: {$fmtWOpen1} – {$fmtWClose1}";
            if (!empty($schedule[1]['has_shift2'])) {
                $fmtWOpen2  = date('H.i', strtotime("1970-01-01 " . $schedule[1]['open_time_2']));
                $fmtWClose2 = date('H.i', strtotime("1970-01-01 " . $schedule[1]['close_time_2']));
                $str .= " & {$fmtWOpen2} – {$fmtWClose2}";
            }
            $parts[] = $str . " WIB";
        } else {
            $parts[] = "Senin – Jumat: Tutup";
        }

        if ($weekendIsOpen) {
            $fmtWeOpen1  = date('H.i', strtotime("1970-01-01 " . $schedule[6]['open_time']));
            $fmtWeClose1 = date('H.i', strtotime("1970-01-01 " . $schedule[6]['close_time']));
            $str = "Sabtu – Minggu: {$fmtWeOpen1} – {$fmtWeClose1}";
            if (!empty($schedule[6]['has_shift2'])) {
                $fmtWeOpen2  = date('H.i', strtotime("1970-01-01 " . $schedule[6]['open_time_2']));
                $fmtWeClose2 = date('H.i', strtotime("1970-01-01 " . $schedule[6]['close_time_2']));
                $str .= " & {$fmtWeOpen2} – {$fmtWeClose2}";
            }
            $parts[] = $str . " WIB";
        } else {
            $parts[] = "Sabtu – Minggu: Tutup";
        }

        return implode('<br>', $parts);
    }
}
