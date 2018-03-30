<?php

namespace ShadowRocket;

class Autoloader
{
    private $psr4 = array(
        'ShadowRocket\\' => 'src/shadowrocket'
    );

    private $project_root = '';

    public function __construct()
    {
        $this->project_root = dirname(dirname(dirname(__FILE__)));

        spl_autoload_register(array($this, 'psr4Loader'));
    }

    public function psr4Loader($name)
    {
        foreach ($this->psr4 as $namespace => $path) {
            if (strpos($name, $namespace) !== false) {
                $path = strtr($path, '\\', DIRECTORY_SEPARATOR);
                if (substr($path, -1, 1) !== DIRECTORY_SEPARATOR) {
                    $path .= DIRECTORY_SEPARATOR;
                }

                $count = 1;
                $file_path = $this->project_root . DIRECTORY_SEPARATOR
                    . str_ireplace($namespace, $path, $name, $count) . '.php';

                if (file_exists($file_path)) {
                    require_once $file_path;
                    return true;
                }
            }
        }
        return false;
    }
}
