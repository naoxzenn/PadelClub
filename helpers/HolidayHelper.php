<?php
// helpers/HolidayHelper.php

class HolidayHelper
{
    /**
     * Check if a given date is a registered holiday
     * @param string $date (Y-m-d)
     * @param PDO|mysqli|null $dbConnection
     * @return bool
     */
    public static function isHoliday($date, $dbConnection = null): bool
    {
        $info = self::getHolidayInfo($date, $dbConnection);
        return $info['is_holiday'];
    }

    /**
     * Get detailed holiday information for a given date
     * @param string $date (Y-m-d)
     * @param PDO|mysqli|null $dbConnection
     * @return array ['is_holiday' => bool, 'title' => string, 'description' => string]
     */
    public static function getHolidayInfo($date, $dbConnection = null): array
    {
        if (empty($dbConnection)) {
            global $pdo, $conn;
            $dbConnection = $pdo ?? $conn;
        }

        $formattedDate = date('Y-m-d', strtotime((string)$date));
        $record = null;

        if ($dbConnection instanceof PDO) {
            try {
                $stmt = $dbConnection->prepare("SELECT * FROM holidays WHERE holiday_date = ? OR date = ? LIMIT 1");
                $stmt->execute([$formattedDate, $formattedDate]);
                $record = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (\Throwable $e) {
                // Table column might only be holiday_date or date
                try {
                    $stmt = $dbConnection->prepare("SELECT * FROM holidays WHERE holiday_date = ? LIMIT 1");
                    $stmt->execute([$formattedDate]);
                    $record = $stmt->fetch(PDO::FETCH_ASSOC);
                } catch (\Throwable $e2) {
                    error_log("HolidayHelper PDO Error: " . $e2->getMessage());
                }
            }
        } elseif ($dbConnection) {
            $sql = "SELECT * FROM holidays WHERE holiday_date = '$formattedDate' LIMIT 1";
            $res = @mysqli_query($dbConnection, $sql);
            if ($res && mysqli_num_rows($res) > 0) {
                $record = mysqli_fetch_assoc($res);
            }
        }

        if ($record && (!isset($record['is_closed']) || (int)$record['is_closed'] !== 0)) {
            $title = $record['holiday_name'] ?? $record['title'] ?? $record['nama_hari_libur'] ?? $record['nama'] ?? 'Hari Libur Venue';
            $description = $record['description'] ?? $record['keterangan'] ?? '';

            return [
                'is_holiday'  => true,
                'title'       => $title,
                'description' => $description
            ];
        }

        return [
            'is_holiday'  => false,
            'title'       => '',
            'description' => ''
        ];
    }
}
