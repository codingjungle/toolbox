<?php

/**
 * @brief       Langs Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox: Code Analyzer
 * @since       1.0.0
 * @version     -storm_version-
 */

namespace IPS\toolbox\Code;

use Exception;
use InvalidArgumentException;
use IPS\Application;
use IPS\Member;
use RuntimeException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use IPS\toolbox\Code\Abstracts\ParserAbstract;

use function abs;
use function array_diff;
use function count;
use function defined;
use function explode;
use function file_exists;
use function header;
use function in_array;
use function is_array;
use function mb_strlen;
use function mb_substr;
use function preg_match_all;
use function trim;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

class _Langs extends ParserAbstract
{
    public $prefixes = [
        'app_',
        '__app_',
        'module__',
        'menu__',
        'frontnavigation__',
        '__indefart',
        '__defart',
        'r__',
        'modeperms__',
        'acplogs__',
        'task__',
        'modlog__',
        '__api',
        'filestorage__',
        'block_',
        'widget_',
        'mobilenavigation_',
        'mailsub__'
    ];
    public $toIgnore = [
        'app_'              => 1,
        '__app_'            => 1,
        'module__'          => 1,
        'menu__'            => 1,
        'frontnavigation__' => 1,
        '__indefart'        => 1,
        '__defart'          => 1,
        'r__'               => 1,
        'modeperms__'       => 1,
        'acplogs__'         => 1,
        'task__'            => 1,
        'modlog__'          => 1,
        '__api'             => 1,
        'filestorage__'     => 1,
        'block_'            => 1,
        'widget_'           => 1,
        'mobilenavigation_' => 1,
        'mailsub__'         => 1
    ];

    public $suffixes = [
        '_pl_lc',
        '_lc',
        '_pl',
        '_desc',

    ];
    /**
     * @var null|array
     */
    protected $langs;
    /**
     * @var null|array
     */
    protected $jslangs;

    /**
     * Array containing keys which should be hidden from the warning list (e.g. we know that __app_foo has to exist,
     * but it won't be used anywhere in the code )
     *
     * @var array
     */
    protected $ignore = [];

    /**
     * {@inheritdoc}
     * @throws InvalidArgumentException
     */
    public function __construct($app)
    {
        parent::__construct($app);
        $this->addKeyToIgnoreList('__app_' . $this->app->directory);
        $this->getLangs();
        $this->skip = [
            'lang.php',
            'jslang.php',
            'lang.xml',
        ];
        if (!($app instanceof Application)) {
            $app = Application::load($app);
        }
        $extensions = $app->extensions('toolbox', 'langPrefixes');
        if (empty($extensions) === false) {
            /** @var LangPrefix $extension */
            foreach ($extensions as $extension) {
                $extension->prefixes($this->prefixes);
                $extension->suffixes($this->suffixes);
            }
        }
    }

    /**
     * Adds a key to the ignore list
     *
     * @param $name
     */
    public function addKeyToIgnoreList($name)
    {
        $this->ignore[] = $name;
    }

    /**
     * builds the lang strings into $this->langs and $this->jslangs
     *
     * @throws InvalidArgumentException
     */
    protected function getLangs()
    {
        if ($this->app === null) {
            return;
        }
        $lf = $this->app->directory . 'dev/lang.php';
        if (file_exists($lf)) {
            $lang = null;
            require $lf;
            $this->langs = $lang;
        }

        $jlf = $this->app->directory . 'dev/jslang.php';
        if (file_exists($jlf)) {
            $lang = null;
            require $jlf;
            $this->jslangs = $lang;
        }
    }

    /**
     * checks to see if the language strings are in use
     *
     * @return array
     * @throws RuntimeException
     * @throws Exception
     */
    public function check(): array
    {
        set_time_limit(0);

        if ($this->files === null) {
            return [];
        }

        $content = $this->getContent();
        $keys = is_array($this->langs) ? $this->langs : [];
        $jskeys = is_array($this->jslangs) ? $this->jslangs : [];
        $warning = [];

        /* Remove the ignored language strings like the app name */
        $keys = array_diff($keys, $this->ignore);

        foreach ($keys as $find => $value) {
            $find = trim($find);
            foreach ($this->suffixes as $suffix) {
                $check = mb_substr($find, -1 * abs(mb_strlen($suffix)));
                if ((string)$check === (string)$suffix) {
                    unset($keys[$find]);
                    continue 2;
                }
            }
            foreach ($this->prefixes as $prefix) {
                $check = mb_substr($find, 0, mb_strlen(trim($prefix)));
                if ((string)$check === (string)$prefix) {
                    $find2 = mb_substr($find, mb_strlen(trim($prefix)), mb_strlen($find));
                    preg_match_all('#[\'|"]' . $find2 . '[\'|"]#msu', $content, $match);

                    if (!count($match[0]) && !isset($this->toIgnore[$prefix])) {
                        $warning['langs'][$find] = $find;
                    } else {
                        unset($keys[$find]);
                    }
                    continue 2;
                }
            }
        }


        foreach ($keys as $find => $value) {
            $find = trim($find);
            preg_match_all('#[\'|"]' . $find . '[\'|"]#msu', $content, $match);
            if (isset($match[0]) && !count($match[0])) {
                $warning['langs'][$find] = $find;
            } else {
                unset($warning['langs'][$find]);
            }
        }

        foreach ($jskeys as $find => $value) {
            $find = trim($find);
            foreach ($this->suffixes as $suffix) {
                $check = mb_substr($find, -1 * abs(mb_strlen($suffix)));
                if ((string)$check === (string)$suffix) {
                    unset($jskeys[$find]);
                    continue 2;
                }
            }
            foreach ($this->prefixes as $prefix) {
                $check = mb_substr($find, 0, mb_strlen(trim($prefix)));
                if ((string)$check === (string)$prefix) {
                    $find2 = mb_substr($find, mb_strlen(trim($prefix)), mb_strlen($find));
                    preg_match_all('#[\'|"]' . $find2 . '[\'|"]#msu', $content, $match);
                    if (!count($match[0]) && !isset($this->toIgnore[$prefix])) {
                        $warning['jslangs'][$find] = $find;
                    } else {
                        unset($jskeys[$find]);
                    }
                    continue 2;
                }
            }
        }

        foreach ($jskeys as $find => $value) {
            $find = trim($find);
            preg_match_all('#[\'|"]' . $find . '[\'|"]#msu', $content, $match);
            if (!count($match[0])) {
                $warning['jslangs'][$find] = $find;
            } else {
                unset($warning['jslangs'][$find]);
            }
        }

        return $warning;
    }

    /**
     * checks to see the language strings in use are defined.
     *
     * @throws RuntimeException
     */
    public function verify(): array
    {
        if ($this->files === null) {
            return [];
        }

        $jskeys = is_array($this->jslangs) ? $this->jslangs : [];
        $warning = [];
        $root = [];
        /**
         * @var SplFileInfo $file
         */
        foreach ($this->files as $file) {
            $data = $file->getContents();
            $line = 1;
            $lines = explode("\n", $data);
            $name = $file->getRealPath();
            foreach ($lines as $content) {
                $path = $this->buildPath($name, $line);
                if ($file->getExtension() === 'phtml') {
                    $matches = [];
                    preg_match_all("#{lang=['|\"](.*?)['|\"]#u", $content, $matches);
                    if (isset($matches[1]) && count($matches[1])) {
                        /* @var array $found */
                        $found = $matches[1];
                        foreach ($found as $key => $val) {
                            $val = trim($val);
                            if ($val && (!in_array(
                                    mb_substr($val, 0, 1),
                                    [
                                        '$',
                                        '{',
                                    ]
                                )) && !Member::loggedIn()->language()->checkKeyExists($val)) {
                                $warning[] = [
                                    'path' => ['url' => $path, 'name' => $name],
                                    'key'  => $val,
                                    'line' => $line
                                ];
                            }
                        }
                    }
                }

                if ($file->getExtension() === 'php') {
                    $matches = [];
                    preg_match_all('/addToStack\((?:\s)[\'|"](.*?)[\'|"]/u', $content, $matches);
                    if (isset($matches[1]) && count($matches[1])) {
                        /* @var array $found */
                        $found = $matches[1];
                        foreach ($found as $key => $val) {
                            $val = trim($val);
                            if ($val && (!in_array(
                                    mb_substr($val, 0, 1),
                                    [
                                        '$',
                                        '{',
                                    ]
                                )) && !Member::loggedIn()->language()->checkKeyExists($val)) {
                                $warning[] = [
                                    'path' => ['url' => $path, 'name' => $name],
                                    'key'  => $val,
                                    'line' => $line
                                ];
                            }
                        }
                    }

                    $matches = [];
                    preg_match_all('/->get\((?:\s)[\'|"](.*?)[\'|"]/u', $content, $matches);
                    if (isset($matches[1]) && count($matches[1])) {
                        /* @var array $found */
                        $found = $matches[1];
                        foreach ($found as $key => $val) {
                            $val = trim($val);
                            if ($val && (!in_array(
                                    mb_substr($val, 0, 1),
                                    [
                                        '$',
                                        '{',
                                    ]
                                )) && !Member::loggedIn()->language()->checkKeyExists($val)) {
                                $warning[] = [
                                    'path' => ['url' => $path, 'name' => $name],
                                    'key'  => $val,
                                    'line' => $line
                                ];
                            }
                        }
                    }
                }

                if ($file->getExtension() === 'js') {
                    $matches = [];
                    preg_match_all('/getString\((?:\s)[\'|"](.*?)[\'|"]/u', $content, $matches);
                    /**
                     * @var array $matches
                     */
                    if (isset($matches[1]) && count($matches[1])) {
                        /* @var array $found */
                        $found = $matches[1];
                        foreach ($found as $key => $val) {
                            $val = trim($val);
                            if ($val && (!in_array(
                                    mb_substr($val, 0, 1),
                                    [
                                        '$',
                                        '{',
                                    ]
                                )) && (!isset($jskeys[$val]) && !Member::loggedIn()->language()->checkKeyExists(
                                        $val
                                    ))) {
                                $warning[] = [
                                    'path' => ['url' => $path, 'name' => $name],
                                    'key'  => $val,
                                    'line' => $line
                                ];
                            }
                        }
                    }
                }
                $line++;
            }
        }
        return $warning;
    }
}
