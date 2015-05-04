<?php

/**
 * Get a list of PHP Supported Timezones that can be used in Carbon.
 * Currently this is limited to America (north/south) timezones.
 * This also calculates the current UTC offset each time it is generated
 *
 *
 * @param string $return Whether to return just the array keys, or the full array
 * @return array A form usable list of PHP supported timezones.
 */
function getSupportedTimezones($return = 'asKeys')
{
    $tz = DateTimeZone::listIdentifiers(DateTimeZone::AMERICA);

    if($return == 'asArray') {
        return $tz;
    }

    $timezones = array();
    foreach($tz as $timezone) {
        $timezones[$timezone] ="[UTC " . ((timezone_offset_get(new DateTimeZone($timezone), new DateTime('now')) / 60)/60) . "] {$timezone}";
    }

    return $timezones;
}

/**
 * Returns whether or not a timezone is supported by PHP.
 *
 * @param string $timezone a string to check
 * @return bool
 */
function timezoneIsSupported($timezone = '')
{
    if(empty($timezone)) return true;

    $tz = getSupportedTimezones('asArray');

    return in_array($timezone, $tz);
}