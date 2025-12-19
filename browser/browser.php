<?php


namespace datagutten\xmltv\browser;


use datagutten\tools\files\files as file_tools;
use datagutten\xmltv\tools\common;
use datagutten\xmltv\tools\exceptions\ChannelNotFoundException;
use datagutten\xmltv\tools\parse\parser;
use Twig;

class browser
{
    public $twig;
    public $root = '/xmltv-browser';
    /**
     * @var common\files
     */
    public $files;
    /**
     * @var common\channel_info
     */
    public $channel_info;

    /**
     * @var parser
     */
    public $xmltv;

    function __construct($config)
    {
        $loader = new Twig\Loader\FilesystemLoader(array('templates'), __DIR__);
        $this->twig = new Twig\Environment($loader, array('debug' => true, 'strict_variables' => true));
        $this->twig->addFilter(new Twig\TwigFilter('time', array($this, 'time')));
        $this->channel_info = new common\channel_info();
        $this->xmltv = new parser($config['xmltv_path'], $config['xmltv_sub_folders']);
        $this->files = $this->xmltv->files;
        $this->twig->addFilter(new Twig\TwigFilter('xml_strtotime', array($this->xmltv, 'strtotime')));
    }

    /**
     * Get available data sources for a channel on a given date
     * @param string $channel
     * @param int $timestamp
     * @return array Source sub folder names
     */
    public function sources(string $channel, int $timestamp): array
    {
        $sources = [];
        $base_folder = file_tools::path_join($this->files->xmltv_path, $channel);
        $sub_folders = file_tools::sub_folders($base_folder);
        foreach ($sub_folders as $folder)
        {
            if (str_contains($folder, 'raw_data'))
                continue;
            $source = basename($folder);
            //$file = common\filename::file_path($channel, $source, $timestamp, 'xml');
            $year_folder = file_tools::path_join($folder, date('Y', $timestamp));
            if (!file_exists($year_folder))
                continue;
            $file = common\filename::filename($channel, $timestamp, 'xml');
            $file = file_tools::path_join($year_folder, $file);
            if (file_exists($file))
                $sources[] = $source;
        }
        return $sources;
    }

    /**
     * Renders a template.
     *
     * @param string $name    The template name
     * @param array  $context An array of parameters to pass to the template
     *
     * @return string The rendered template
     *
     */
    public function render($name, $context)
    {
        $context = array_merge($context, array(
            'root'=>$this->root,
            'program_filter' => $_GET['program'] ?? null,
            'today' => date('Y-m-d')));
        try {
            return $this->twig->render($name, $context);
        }
        catch (Twig\Error\Error $e) {

            //$trace = sprintf('<pre>%s</pre>', $e->getTraceAsString());
            $msg = "Error rendering template:\n" . $e->getMessage();
            try {
                die($this->twig->render('error.twig', array(
                        'root'=>$this->root,
                        'title'=>'Rendering error',
                        'error'=>$msg,
                        'trace'=>$e->getTraceAsString())
                ));
            }
            catch (Twig\Error\Error $e_e)
            {
                $msg = sprintf("Original error: %s\n<pre>%s</pre>\nError rendering error template: %s\n<pre>%s</pre>",
                    $e->getMessage(), $e->getTraceAsString(), $e_e->getMessage(), $e_e->getTraceAsString());
                die($msg);
            }
            //die($this->render($this->render()))
        }
    }
    public function time($time)
    {
        $time = $this->xmltv->strtotime($time);
        return date('H:i', $time);
    }

    /**
     * Get a list of all channels
     * @return array Array with channel id as key and name (if found) as value
     */
    public function channel_list()
    {
        $channels = scandir($this->files->xmltv_path);
        $channel_list = [];
        foreach ($channels as $channel)
        {
            if(!preg_match('/[a-z0-9]+\.[a-z]+/',$channel))
                continue;
            if(!is_dir($this->files->xmltv_path.'/'.$channel))
                continue;

            $valid = false;
            foreach ($this->files->sub_folders as $sub_folder)
            {
                if(file_exists($this->files->xmltv_path.'/'.$channel.'/'.$sub_folder)) {
                    $valid = true;
                    break;
                }
                else {
                    $valid = false;
                }
            }
            if(!$valid)
                continue;

            try {
                $name = $this->channel_info->id_to_name($channel);
                $channel_list[$channel] = $name;
            }
            catch (ChannelNotFoundException $e)
            {
                $channel_list[$channel] = $channel;
            }
        }
        asort($channel_list);
        return $channel_list;
    }

    public function render_channel_list(): string
    {
        return $this->render('select_channel.twig', array(
            'channels' => $this->channel_list(),
            'sources' => [],
        ));
    }

}