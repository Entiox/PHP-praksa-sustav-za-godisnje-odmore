<?php

namespace App\Util;

use DateInterval;
use DatePeriod;
use Yasumi\Yasumi;

class DateWorker
{
    private static $holidayDates;

    public static function initializeHolidayDates()
    {
        self::$holidayDates = array_merge( array_values(Yasumi::create('Croatia', (int) date('Y'))->getHolidayDates()),
        array_filter(array_values(Yasumi::create('Croatia', (int) date('Y') + 1)->getHolidayDates()), function($holidayDate) {
            return date('m', strtotime($holidayDate)) < 2;
        }));
    }

    public static function getHolidayDates()
    {
        if (self::$holidayDates === null) {
            self::initializeHolidayDates();
        }

        return self::$holidayDates;
    }

    public static function calculateWorkingDays(\DateTimeInterface $startingDate, \DateTimeInterface $endingDate)
    {
        $workingDays = 0;

        $period = new DatePeriod($startingDate, DateInterval::createFromDateString('1 day'), $endingDate, DatePeriod::INCLUDE_END_DATE);

        foreach ($period as $date) {
            if ($date->format('N') < 6 && !in_array($date->format("Y-m-d"), self::getHolidayDates())) {
                $workingDays++;
            }
        }

        return $workingDays;
    }
}