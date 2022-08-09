<?php

/**
 * @brief       Database Standard
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  toolbox
 * @since       -storm_since_version-
 * @version     -storm_version-
 */

namespace IPS\toolbox\Profiler\Parsers;

use IPS\Db;
use IPS\Patterns\Singleton;
use IPS\Theme;
use IPS\toolbox\Editor;
use UnexpectedValueException;

use function count;
use function defined;
use function explode;
use function file_exists;
use function header;
use function htmlspecialchars;
use function round;
use function sha1;

use function str_replace;

use const ENT_DISALLOWED;
use const ENT_QUOTES;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

class _Database extends Singleton
{
    public static $slowest;
    public static $slowestLink;

    /**
     * @brief   Singleton Instances
     * @note    This needs to be declared in any child class.
     */
    protected static $instance;

    /**
     * query store
     *
     * @var array
     */
    protected $dbQueries = [];

    /**
     * _Database constructor.
     */
    public function __construct()
    {
        $this->dbQueries = Db::i()->log;
    }

    /**
     * builds the database button
     *
     * @return mixed
     * @throws UnexpectedValueException
     */
    public function build()
    {
        $list = [];
//        $hash = [];
        $dbs = $this->dbQueries;
//        $cache = md5(time());

        foreach ($dbs as $db) {
            $h = sha1($db['query']);

            $time = null;
            if (isset($db['time'])) {
                $time = round($db['time'], 4);
            }

            if ($time !== null) {
                if (static::$slowest === null) {
                    static::$slowest = $time;
                    static::$slowestLink = $h;
                } elseif ($time > static::$slowest) {
                    static::$slowest = $time;
                    static::$slowestLink = $h;
                }
            }
            $mem = null;
            if (isset($db['mem'])) {
                $mem = $db['mem'];
            }
            $code = true;
            $bt = '<div class="dtProfilerDatabase">';
            if (\IPS\DEV_WHOOPS_EDITOR) {
                $dbt = [];
                eval("\$dbt = {$db['backtrace']};");
                $code = false;
                foreach ($dbt as $i => $v) {
                    $file = $v['file'] ?? null;
                    if(str_contains($file,'hook_temp')){
                        if(!file_exists($file)){
                            $path = \IPS\ROOT_PATH . '/hook_temp/';
                            $parts = explode('.php_',$file);
                            $name = str_replace($path,'',$parts[0]);
                            $finder = new \Symfony\Component\Finder\Finder();
                            $finder->in( $path )->files()->name($name.'*.php');
                            $list = null;
                            foreach($finder as $f){
                                $list = $f->getRealPath();
                            }
                            if($list === null){
                                continue;
                            }
                            $file = $list;
                        }
                    }
                    $line = $v['line'] ?? 0;
                    $link = '#';
                    $class = $v['class'] ?? '';
                    $type = $v['type'] ?? '';
                    $func = $v['function'] ?? '';
                    if ($file) {
                        $link = (new Editor())->replace($file, $line);
                    }
                    if ($line) {
                        $line = ' Line: ' . $line;
                    } else {
                        $line = '';
                    }
                    $bt .= <<<EOF
<div class="ipsPadding:half ipsBorder_bottom" style="word-break: break-all;">
    <a href="{$link}">
        #{$i} {$file}({$line}): {$class}{$type}{$func}() 
        </a>
</div>
EOF;
                }
            } else {
                $bt .= $db['backtrace'];
            }
            $bt .= "</div>";

            $query = htmlspecialchars($db['query'], ENT_DISALLOWED | ENT_QUOTES, 'UTF-8', true);
            $list[] = [
                'id' => $h,
                'server' => $db['server'] ?? null,
                'query' => $query,
                'bt' => $bt,
//                'url'    => $url,
                'time' => $time,
                'mem' => $mem,
                'code' => $code
            ];
        }

//        Store::i()->dtprofiler_bt = $hash;
        return Theme::i()->getTemplate('database', 'toolbox', 'front')->database($list, count($list));
    }
}
