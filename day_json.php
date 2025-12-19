<?php

use datagutten\xmltv\browser\browser;
use datagutten\xmltv\tools\data\Program;

require __DIR__ . '/vendor/autoload.php';
$config = require 'config.php';
$browser = new browser($config);

$start = strtotime($_GET['date'] ?? 'today');
$channel = $_GET['channel'] ?? 'natgeo.no';

if (empty($channel))
    echo json_encode($this->channel_list());
else
{
    $programs_xml = $browser->xmltv->get_programs($channel, $start);
    $programs = array_map(function ($program)
    {
        return Program::fromXMLTV($program);
    }, $programs_xml);

    echo json_encode($programs);
}