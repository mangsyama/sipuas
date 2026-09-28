<?php

namespace App\Traits;

use Illuminate\Support\Carbon;

trait NormalizesTimestamps
{
    /**
     * Return a timestamp as DateTime object with proper application timezone handling.
     *
     * Ensures consistent timezone interpretation across database drivers (especially SQL Server / sqlsrv)
     * where DATETIMEOFFSET fields saved with local time (WITA) default to +00:00 (UTC) offset
     * when written without an explicit timezone string.
     *
     * @param  mixed  $value
     * @return \Illuminate\Support\Carbon|null
     */
    protected function asDateTime($value)
    {
        $date = parent::asDateTime($value);

        if ($date instanceof Carbon) {
            $appTimezone = config('app.timezone', 'Asia/Makassar');
            $tzName = $date->getTimezone()->getName();

            // When database driver returns +00:00 / UTC / Z for a datetime
            // originally recorded in application local time:
            if ($tzName === 'UTC' || $tzName === '+00:00' || $tzName === 'Z') {
                return $date->shiftTimezone($appTimezone);
            }

            if ($tzName !== $appTimezone) {
                return $date->setTimezone($appTimezone);
            }
        }

        return $date;
    }
}
