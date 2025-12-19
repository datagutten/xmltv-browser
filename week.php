<?php
require 'vendor/autoload.php';

use datagutten\xmltv\browser\browser;
use datagutten\xmltv\tools\exceptions\InvalidXMLFileException;
use datagutten\xmltv\tools\parse;
use Twig\TwigFilter;

$config = require 'config.php';
$browser = new browser($config);
$xmltv = $browser->xmltv;
$browser->xmltv->ignore_timezone = false;
if (empty($_GET['channel']) && !empty($argv[1]))
    $_GET['channel'] = $argv[1];


//http://zetcode.com/php/twig/
function format_day($day)
{
    return $day[0]->start_obj->format("l\nY-m-d");
}
$browser->twig->addFilter(new TwigFilter('weekday', 'format_day'));
function isoformat_day($day): string
{
    return $day[0]->start_obj->format("Y-m-d");
}
$browser->twig->addFilter(new TwigFilter('isodate', 'isoformat_day'));

$limit = 14;
if(!empty($_GET['channel'])) {
    $channel = $_GET['channel'];
    $start = strtotime($_GET['date'] ?? 'today');
    $sources = $browser->sources($channel, $start);
    if (empty($sources))
        die($browser->render('error.twig', ['error' => 'No programs found']));
    if(!empty($_GET['sub_folder']))
        $browser->files->sub_folders = [$_GET['sub_folder']];
    $days = [];
    for ($timestamp = $start; $timestamp <= strtotime('today') + 86400 * $limit; $timestamp = $timestamp + 86400) {
        try
        {
            $programs = $xmltv->get_programs($_GET['channel'], $timestamp);
            if (isset($_GET['program']))
            {
                $programs = $xmltv->filter_programs($programs, $_GET['program']);
                if (empty($programs))
                    continue;
            }
            $days[] = array_map('datagutten\xmltv\browser\ProgramWeb::fromXMLTV', $programs);
        }
        catch (FileNotFoundException|InvalidXMLFileException $e)
        {
            continue;
        }
    }

    echo $browser->render('week.twig', array('days' => $days, 'channel'=>$_GET['channel'], 'sources' => $sources));
}
else
{
    $channels = $browser->channel_list();
    echo $browser->render('select_channel.twig', array('channels' => $channels, 'sources' => []));
}