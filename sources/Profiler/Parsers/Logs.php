<?php

/**
 * @brief       Logs Standard
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  toolbox
 * @since       -storm_since_version-
 * @version     -storm_version-
 */

namespace IPS\toolbox\Profiler\Parsers;

use IPS\Db;
use IPS\Log;
use Exception;
use Throwable;
use IPS\DateTime;
use IPS\Http\Url;
use IPS\Settings;
use IPS\cms\Theme;

use IPS\toolbox\Editor;
use IPS\Patterns\Singleton;
use UnexpectedValueException;

use IPS\Patterns\ActiveRecordIterator;

use function _p;
use function trim;
use function count;
use function nl2br;
use function rtrim;
use function header;
use function defined;
use function explode;
use function implode;
use function preg_match;
use function file_exists;
use function str_replace;
use function htmlentities;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * Class _Logs
 *
 * @package IPS\toolbox\Parsers
 * @mixin \IPS\toolbox\Parsers\Logs
 */
class _Logs extends Singleton
{
    /**
     * @brief   Singleton Instances
     * @note    This needs to be declared in any child class.
     */
    protected static $instance;

    /**
     * Builds the logs button
     *
     * @return string|null
     * @throws UnexpectedValueException
     */
    public function build()
    {
        if (!Settings::i()->dtprofiler_enabled_logs) {
            return null;
        }

        $sql = Db::i()->select('*', 'core_log', null, 'id DESC', Settings::i()->dtprofiler_logs_amount);
        $logs = new ActiveRecordIterator($sql, Log::class);
        $list = [];

        /* @var Log $log */
        foreach ($logs as $log) {
            $url = Url::internal('app=toolbox&module=bt&controller=bt', 'front')->setQueryString([
                'do' => 'log',
                'id' => $log->id,
            ]);
            $data = DateTime::ts($log->time);
            $name = 'Date: ' . $data;
            if ($log->category !== null) {
                $name .= '<br> Type: ' . $log->category;
            }

            if ($log->url !== null) {
                $name .= '<br> URL: ' . $log->url;
            }

            $body = $log->message;
            $msg = nl2br(htmlentities($body));
            if (str_contains($body, 'Stack trace:')) {
                $body = explode("\n", $body);
                $keep = [];
                $process = [];
                $replace = null;
                $i = 0;
                foreach ($body as $b) {
                    preg_match('#\#([0-9]|[1-9][0-9]|[1-9][0-9][0-9]|[1-9][0-9][0-9][0-9])\s(.*?):(.*?)\(#i', trim($b), $matches);
                    if (empty($matches) === true) {
                        $keep[$i] = $b.'<br>';
                    } else {
                        if ($replace === null) {
                            $replace = $i;
                            $keep[$i] = 1;
                        }
                        $process[] = $b;
                    }
                    $i++;
                }
                $newMsg = '';
                \IPS\toolbox\Profiler\Parsers\Logs::process($newMsg, $process, true);
                $keep[$replace] = $newMsg;
                $msg = '<h5>Log Message</h5>';
                $msg .= implode("\n", $keep);
            }
            $name .= '<br>'.$msg;
            $name .= '<h5>Backtrace</h5>';

            if (\IPS\DEV_WHOOPS_EDITOR) {
                $dbt = explode("\n", $log->backtrace);
                try {
                    \IPS\toolbox\Profiler\Parsers\Logs::process($name, $dbt, true);
                } catch (Throwable | Exception $e) {
                }
            } else {
                $name .= '<pre class="prettyprint lang-php">' . $log->backtrace . '</pre>';
            }
            $list[] = Theme::i()->getTemplate('generic', 'toolbox', 'front')->logs($name);
        }

        return Theme::i()
                    ->getTemplate('generic', 'toolbox', 'front')
                    ->button('Logs', 'logs', 'list of logs', $list, count($list), 'list', true, false);
    }

    public static function process(&$output, $dbt, $noWrapper = false, $id=0)
    {
        if ($noWrapper === false) {
            $output .= '<div class="ipsBorder ipsPadding:half ipsMargin_top">';
        }
        foreach ($dbt as $i => $v) {
            preg_match('#\#([0-9]|[1-9][0-9]|[1-9][0-9][0-9]|[1-9][0-9][0-9][0-9])\s(.*?):(.*?)\((.*?)$#i', trim($v), $matches);

            $protoFile = $matches[2] ?? null;
            if ($protoFile === null) {
                continue;
            }
            preg_match('#^(.*)\((.*?)\)$#i', $protoFile, $m1);
            $editor = true;
            if (!isset($m[1])) {
                $file = $protoFile;
                $editor = false;
            } else {
                $file = $m1[1];
                if (str_contains($file, 'hook_temp')) {
                    if (!file_exists($file)) {
                        $path = \IPS\ROOT_PATH . '/hook_temp/';
                        $parts = explode('.php_', $file);
                        $name = str_replace($path, '', $parts[0]);
                        $finder = new \Symfony\Component\Finder\Finder();
                        $finder->in($path)->files()->name($name . '*.php');
                        $list = null;
                        foreach ($finder as $f) {
                            $list = $f->getRealPath();
                        }
                        if ($list === null) {
                            continue;
                        }
                        $file = $list;
                    }
                }
            }
            $line = $m1[2] ?? 0;

            $link = '#';
            $class = $matches[3] ?? '';
            $params = $matches[4] ?? '';
            $params = rtrim($params, ')');
            if ($editor) {
                $link = (new Editor())->replace($file, $line);
            }
            $ccs = ' ';
            if ($noWrapper === false) {
                $ccs = ' class="ipsBorder_bottom ipsMargin_bottom:half" ';
            }
            $template = <<<EOF
<div{$ccs}style="word-break: break-all;">
    <a href="{$link}">
        #{$i} {$file}({$line}): {$class}({$params})
    </a>
</div>
EOF;
            $output .= $template;
        }
        if ($noWrapper === false) {
            $output .= '</div>';
        }
    }
}
