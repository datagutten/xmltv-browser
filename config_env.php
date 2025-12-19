<?php
$config['xmltv_path'] = getenv('XMLTV_PATH') ?? '/home/xmltv';

$folders_env = getenv('XMLTV_SUB_FOLDERS');
if (!empty($folders_env))
    $config['xmltv_sub_folders'] = explode(' ', $folders_env);
else
    $config['xmltv_sub_folders'] = '/home/xmltv';

return $config;