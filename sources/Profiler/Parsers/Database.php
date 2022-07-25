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

use IPS\Data\Store;
use IPS\Db;
use IPS\Http\Url;
use IPS\Patterns\Singleton;
use IPS\Theme;

use UnexpectedValueException;

use function count;
use function defined;
use function header;
use function htmlentities;
use function htmlspecialchars;
use function md5;
use function round;
use function sha1;
use function time;

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
        $hash = [];
        $dbs = $this->dbQueries;
        $cache = md5(time());

        foreach ($dbs as $db) {
            $h = sha1($db['query']);
            $hash[$h] = ['query' => $db['query'], 'bt' => $db['backtrace']];
            $url = Url::internal('app=toolbox&module=bt&controller=bt', 'front')->setQueryString([
                'bt'    => $h,
                'cache' => $cache,
            ]);
            $time = null;
            if (isset($db['time'])) {
                $time = round($db['time'], 4);
            }

            if ($time !== null) {
                if (static::$slowest === null) {
                    static::$slowest = $time;
                    static::$slowestLink = $url;
                } elseif ($time > static::$slowest) {
                    static::$slowest = $time;
                    static::$slowestLink = $url;
                }
            }

            $mem = null;
            if (isset($db['mem'])) {
                $mem = $db['mem'];
            }
            $query = htmlspecialchars( $db['query'], ENT_DISALLOWED| ENT_QUOTES, 'UTF-8', true );
            $list[] = [
                'server' => $db['server'] ?? null,
                'query'  => $query,
                'url'    => $url,
                'time'   => $time,
                'mem'    => $mem,
            ];
        }

        Store::i()->dtprofiler_bt = $hash;
        return Theme::i()->getTemplate('database', 'toolbox', 'front')->database($list, count($list));
    }
}
