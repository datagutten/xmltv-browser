<?php
require 'vendor/autoload.php';

use datagutten\xmltv\browser\browser;
use datagutten\xmltv\tools\exceptions\InvalidXMLFileException;
use datagutten\xmltv\tools\parse;
use Twig\TwigFilter;

$xmltv = new parse\parser;
$browser = new browser();
//http://zetcode.com/php/twig/
$browser->twig->addFilter(new TwigFilter('episode', array($xmltv, 'season_episode')));
function format_day($day)
{
    $datetime = $day[0]->attributes()->{'start'};
    $timestamp = strtotime($datetime);
    return date("l\nY-m-d", $timestamp);
}
$browser->twig->addFilter(new TwigFilter('weekday', 'format_day'));

if(!empty($_GET['channel'])) {
    $days = [];
    for ($timestamp = strtotime('today'); $timestamp <= strtotime('today') + 86400 * 7; $timestamp = $timestamp + 86400) {
        try {
            $programs = $xmltv->get_programs($_GET['channel'], $timestamp);
            if (isset($_GET['program'])) {
                $programs = $xmltv->filter_programs($programs, $_GET['program']);
                if (empty($programs))
                    continue;
            }
            $days[] = $programs;
        }
        catch (FileNotFoundException|InvalidXMLFileException $e)
        {
            continue;
        }
    }

    echo $browser->render('week.twig', array('days' => $days, 'channel'=>$_GET['channel']));
}
else
{
    $channels = $browser->channel_list();
    echo $browser->render('select_channel.twig', array('channels' => $channels));
}