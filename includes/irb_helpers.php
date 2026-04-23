<?php

if (!function_exists('irb_convert_to_arabic_digits')) {
    function irb_convert_to_arabic_digits($value)
    {
        return strtr((string) $value, [
            '0' => '٠',
            '1' => '١',
            '2' => '٢',
            '3' => '٣',
            '4' => '٤',
            '5' => '٥',
            '6' => '٦',
            '7' => '٧',
            '8' => '٨',
            '9' => '٩',
        ]);
    }
}

if (!function_exists('irb_format_arabic_date')) {
    function irb_format_arabic_date($dateValue)
    {
        if (empty($dateValue)) {
            return 'غير متوفر';
        }

        try {
            $date = new DateTime($dateValue);
            $arabicMonths = [
                1 => 'يناير',
                2 => 'فبراير',
                3 => 'مارس',
                4 => 'أبريل',
                5 => 'مايو',
                6 => 'يونيو',
                7 => 'يوليو',
                8 => 'أغسطس',
                9 => 'سبتمبر',
                10 => 'أكتوبر',
                11 => 'نوفمبر',
                12 => 'ديسمبر',
            ];

            $day = irb_convert_to_arabic_digits($date->format('j'));
            $month = $arabicMonths[(int) $date->format('n')];
            $year = irb_convert_to_arabic_digits($date->format('Y'));

            return $day . ' ' . $month . ' ' . $year;
        } catch (Exception $exception) {
            return (string) $dateValue;
        }
    }
}

if (!function_exists('irb_format_arabic_time')) {
    function irb_format_arabic_time($dateValue)
    {
        if (empty($dateValue)) {
            return 'غير متوفر';
        }

        try {
            $date = new DateTime($dateValue);
            $time = irb_convert_to_arabic_digits($date->format('g:i'));
            $period = $date->format('A') === 'AM' ? 'ص' : 'م';

            return $time . ' ' . $period;
        } catch (Exception $exception) {
            return '';
        }
    }
}
