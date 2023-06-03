<?php

/**
 * @brief       Editors Standard
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  toolbox
 * @since       -storm_since_version-
 * @version     -storm_version-
 */

namespace IPS\toolbox;

use function rawurlencode;
use function str_replace;

use const DT_WSL_PATH;


class _Editor
{
    /**
     * List of editors to build the url for
     *
     * @var array
     */
    protected static $editors = [
        "sublime"  => "subl://open?url=file://%file&line=%line",
        "textmate" => "txmt://open?url=file://%file&line=%line",
        "emacs"    => "emacs://open?url=file://%file&line=%line",
        "macvim"   => "mvim://open/?url=file://%file&line=%line",
        "phpstorm" => "phpstorm://open?file=%file&line=%line",
        "idea"     => "idea://open?file=%file&line=%line",
        "vscode"   => "vscode://file/%file:%line",
        "atom"     => "atom://core/open/file?filename=%file&line=%line",
        "espresso" => "x-espresso://open?filepath=%file&lines=%line",
        "netbeans" => "netbeans://open/?f=%file:%line",
    ];

    /**
     * Editors constructor.
     */
    public function __construct()
    {
    }

    /**
     * builds a url for the file to open it up in an editor
     *
     * @param string $path
     * @param int $line
     *
     * @return mixed|null
     */
    public function replace(string $path, int $line = 0)
    {
        if (isset(static::$editors[\IPS\DEV_WHOOPS_EDITOR])) {
            $editor = static::$editors[\IPS\DEV_WHOOPS_EDITOR];
            if(defined('DT_USE_WSL') && DT_USE_WSL === true){
                $path =str_replace(['//','/'],['\\','\\'], $path);

                $path = DT_WSL_PATH . $path;
            }
            $path = rawurlencode($path);
            if ($line === null) {
                $line = 0;
            }
            return str_replace(['%file', '%line'], [$path, $line], $editor);
        }

        return null;
    }
}
