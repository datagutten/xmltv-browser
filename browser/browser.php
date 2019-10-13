<?php


namespace datagutten\xmltv\browser;


use datagutten\xmltv\tools\common;
use datagutten\xmltv\tools\exceptions\ChannelNotFoundException;
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

    function __construct()
    {
        $loader = new Twig\Loader\FilesystemLoader(array('templates'), __DIR__);
        $this->twig = new Twig\Environment($loader, array('debug' => true, 'strict_variables' => true));
        $this->twig->addFilter(new Twig\TwigFilter('time', array($this, 'time')));
        $this->files = new common\files();
        $this->channel_info = new common\channel_info();
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
            'root'=>$this->root));
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
        $time = strtotime($time);
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

            $sub_folders = array_merge([$this->files->default_sub_folder], $this->files->alternate_sub_folders);

            $valid = false;
            foreach ($sub_folders as $sub_folder)
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
}