<?php

/**
 * @brief       Template Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox: Code Analyzer
 * @since       1.0.0
 * @version     -storm_version-
 */

namespace IPS\toolbox\Code;

use IPS\toolbox\Application;
use Symfony\Component\Finder\SplFileInfo;

use function defined;
use function explode;
use function header;
use function mb_strlen;
use function mb_strpos;
use function mb_strtoupper;
use function mb_substr;
use function preg_match;
use function str_replace;
use function trim;


if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

class _ErrorCodes extends ParserAbstract
{

    protected $finder;

    /**
     * @inheritdoc
     */
    public function check(): array
    {
        if ($this->files === null) {
            return [];
        }

        $warning = [];
        $codes = [];
        $dupes = [];
        $reg = '#(\d)(.*?)[0-9](.*?)$#msu';
        $possibleIPSCodes = [
            'C' => 1,
            'F' => 1,
            'B' => 1,
            'G' => 1,
            'D' => 1,
            'T' => 1,
            'X' => 1,
            'H' => 1,
            'L' => 1,
            'S' => 1,
            'V' => 1,
        ];
        $altCodes = [];
        $altCodeFile = Application::getRootPath().'/dtProxy/altcodes.json';
        if(\file_exists($altCodeFile)){
            $altCodes = json_decode(\file_get_contents($altCodeFile),true);
        }
        /**
         * @var SplFileInfo $file
         */
        foreach ($this->files as $file) {
            $data = $file->getContents();
            $line = 1;
            $lines = preg_split("/\n|\r\n|\n/",   $data );
            $name = $file->getRealPath();
            $foo = $this;
            foreach ($lines as $content) {
                $path = $this->buildPath($name, $line);
                preg_replace_callback(
                    '#[0-9]{1}([a-zA-Z]{1,})[0-9]{1,}/[a-zA-Z0-9]{1,}#msu',
                     function ($m) use ($line, $path, $possibleIPSCodes, &$codes, &$warning, &$dupes,$altCodes,$foo) {
                        if (!isset($m[1])) {
                            return;
                        }
                        $c = trim($m[0]);
                            if (isset($codes[$c])) {
                                $dupes[] = [
                                    'path' => ['url' => $path, 'name' => $c],
                                    'key'  => 'ERROR_CODES',
                                    'line' => $line
                                ];
                            }
                            $codes[$c] = $c;
                            $cc = mb_strtoupper($m[1]);
                            $loc = $altCodes[$c] ?? [];
                            $locs = [];
                            if(empty($loc) === false) {
                                foreach($loc as $l){
                                    $l['url'] = $foo->buildPath($l['path'],$l['line']);
                                    $locs[] = $l;
                                }
                            }
                            if (isset($possibleIPSCodes[$cc])) {
                                    $warning[] = [
                                        'path' => ['url' => $path, 'name' => $c],
                                        'key'  => 'ERROR_CODES',
                                        'line' => $line,
                                        'loc' => $locs
                                    ];
                                }
                        return null;
                    },
                    str_replace(['"',"'",','],'',trim($content))
                );
                $line++;
            }
        }
        return ['warnings' => $warning, 'dupes' => $dupes];
    }

}
