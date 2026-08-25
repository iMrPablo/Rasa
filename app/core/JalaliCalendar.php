<?php
/**
 * Jalali Calendar Utilities
 */

class JalaliCalendar {
    
    /**
     * Convert Gregorian to Jalali
     */
    public static function gregorianToJalali(int $gy, int $gm, int $gd): array {
        $d = self::d2j(self::g2d($gy, $gm, $gd));
        return [$d['jy'], $d['jm'], $d['jd']];
    }
    
    /**
     * Convert Jalali to Gregorian
     */
    public static function jalaliToGregorian(int $jy, int $jm, int $jd): array {
        $r = self::jalCal($jy);
        $jdn1f = self::g2d($r['gy'], 3, $r['march']);
        $k = self::j2d($jy, $jm, $jd) - $jdn1f;
        
        if ($k >= 0) {
            if ($k <= 185) {
                return self::d2g(self::g2d($r['gy'], 3, $r['march']) + $k);
            }
            $k -= 186;
        } else {
            $jy -= 1;
            $k += 179;
            if ($r['leap'] === 1) {
                $k += 1;
            }
        }
        
        $g = self::d2g(self::g2d($r['gy'], 3, $r['march']) + 186 + $k);
        return [$g['gy'], $g['gm'], $g['gd']];
    }
    
    /**
     * Format Jalali date
     */
    public static function formatJalali(int $jy, int $jm, int $jd): string {
        return sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
    }
    
    /**
     * Get current Jalali date
     */
    public static function currentJalali(): array {
        $now = new DateTime();
        $gy = (int)$now->format('Y');
        $gm = (int)$now->format('n');
        $gd = (int)$now->format('j');
        return self::gregorianToJalali($gy, $gm, $gd);
    }
    
    private static function jalDiv(int $a, int $b): int {
        return intdiv($a, $b);
    }
    
    private static function jalMod(int $a, int $b): int {
        $a = (int)$a;
        $b = (int)$b;
        return $a - intdiv($a, $b) * $b;
    }
    
    private static function jalCal(int $jy): array {
        $breaks = [-61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210, 1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178];
        $gy = $jy + 621;
        $leapJ = -14;
        $jp = $breaks[0];
        $jump = 0;
        $bl = count($breaks);
        
        for ($i = 1; $i < $bl; $i++) {
            $jm = $breaks[$i];
            $jump = $jm - $jp;
            if ($jy < $jm) {
                break;
            }
            $leapJ = $leapJ + self::jalDiv($jump, 33) * 8 + self::jalDiv(self::jalMod($jump, 33), 4);
            $jp = $jm;
        }
        
        $n = $jy - $jp;
        $leapJ = $leapJ + self::jalDiv($n, 33) * 8 + self::jalDiv(self::jalMod($n, 33) + 3, 4);
        
        if (self::jalMod($jump, 33) === 4 && $jump - $n === 4) {
            $leapJ += 1;
        }
        
        $leapG = self::jalDiv($gy, 4) - self::jalDiv((self::jalDiv($gy, 100) + 1) * 3, 4) - 150;
        $march = 20 + $leapJ - $leapG;
        
        if ($jump - $n < 6) {
            $n = $n - $jump + self::jalDiv($jump + 4, 33) * 33;
        }
        
        $leap = self::jalMod(self::jalMod($n + 1, 33) - 1, 4);
        if ($leap === -1) {
            $leap = 4;
        }
        
        return ['leap' => $leap, 'gy' => $gy, 'march' => $march];
    }
    
    private static function g2d(int $gy, int $gm, int $gd): int {
        return self::jalDiv(($gy + self::jalDiv($gm - 8, 6) + 100100) * 1461, 4)
            + self::jalDiv(153 * self::jalMod($gm + 9, 12) + 2, 5)
            + $gd - 34840408
            - self::jalDiv(self::jalDiv($gy + 100100 + self::jalDiv($gm - 8, 6), 100) * 3, 4)
            + 752;
    }
    
    private static function d2g(int $jdn): array {
        $j = 4 * $jdn + 139361631;
        $j = $j + self::jalDiv(self::jalDiv(4 * $jdn + 183187720, 146097) * 3, 4) * 4 - 3908;
        $i = self::jalDiv(self::jalMod($j, 1461), 4) * 5 + 308;
        $gd = self::jalDiv(self::jalMod($i, 153), 5) + 1;
        $gm = self::jalMod(self::jalDiv($i, 153), 12) + 1;
        $gy = self::jalDiv($j, 1461) - 100100 + self::jalDiv(8 - $gm, 6);
        return ['gy' => $gy, 'gm' => $gm, 'gd' => $gd];
    }
    
    private static function j2d(int $jy, int $jm, int $jd): int {
        $r = self::jalCal($jy);
        return self::g2d($r['gy'], 3, $r['march']) + ($jm - 1) * 31 - self::jalDiv($jm, 7) * ($jm - 7) + $jd - 1;
    }
    
    private static function d2j(int $jdn): array {
        $g = self::d2g($jdn);
        $gy = $g['gy'];
        $jy = $gy - 621;
        $r = self::jalCal($jy);
        $jdn1f = self::g2d($gy, 3, $r['march']);
        $k = $jdn - $jdn1f;
        
        if ($k >= 0) {
            if ($k <= 185) {
                return [
                    'jy' => $jy,
                    'jm' => 1 + self::jalDiv($k, 31),
                    'jd' => self::jalMod($k, 31) + 1
                ];
            }
            $k -= 186;
        } else {
            $jy -= 1;
            $k += 179;
            if ($r['leap'] === 1) {
                $k += 1;
            }
        }
        
        return [
            'jy' => $jy,
            'jm' => 7 + self::jalDiv($k, 30),
            'jd' => self::jalMod($k, 30) + 1
        ];
    }
}
