<?php

namespace datagutten\xmltv\browser;

use datagutten\xmltv\tools\data\Program;
use DateTimeZone;

date_default_timezone_set('Europe/Oslo');

class ProgramWeb extends Program
{
    public function header_web(): string
    {
        $start = $this->start_obj->setTimezone(new DateTimeZone('Europe/Oslo'));
        if (isset($this->end_obj))
        {
            $end = $this->end_obj->setTimezone(new DateTimeZone('Europe/Oslo'));
            $header = sprintf("%s-%s\n%s", $start->format('H:i'), $end->format('H:i'), $this->title);
        }
        else
        {
            $header = sprintf("%s\n%s", $start->format('H:i'), $this->title);
        }
        if (!empty($this->episode))
            $header .= ' ' . $this->formatEpisode();
        return $header;
    }

}