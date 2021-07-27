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

use Symfony\Component\Finder\SplFileInfo;

use function defined;
use function header;
use function mb_strlen;
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
            'S'=>1,
            'V'=>1,
        ];
        /**
         * @var SplFileInfo $file
         */
        foreach ($this->files as $file) {
            $data = $file->getContents();
            $line = 1;
            $lines = \explode("\n", $data);
            $name = $file->getRealPath();
            foreach ($lines as $content) {
                $path = $this->buildPath($name, $line);
                preg_replace_callback('#Output::i\(\)->error\((.*?),(.*?)[,|)](.*?)$#msu',
                    static function ($m) use ($reg,$line,$path,$possibleIPSCodes,&$codes,&$warning,&$dupes) {
                        if (!isset($m[2])) {
                            return;
                        }

                        $c = trim(str_replace(['"', "'"], '', trim($m[2])));
                        $first = mb_substr($c, 0, 1);
                        if (
                            $c &&
                            (int)$first &&
                            \mb_strpos($c, '$') === false &&
                            \mb_strpos($c,'<') === false &&
                            $c != 'FALSE' &&
                            $c != 'false'
                        ) {
                            if(isset($codes[$c])){
                                $dupes[] = [
                                    'path' => ['url' => $path, 'name' => $c],
                                    'key'  => 'ERROR_CODES',
                                    'line' => $line
                                ];
                            }
                            $codes[$c] = $c;
                            preg_match($reg, $c, $matches);
                            if(isset($matches[2]) && mb_strlen($matches[2]) === 1){
                               $cc = mb_strtoupper($matches[2]);
                               if(isset($possibleIPSCodes[$cc])){
                                   $warning[] = [
                                       'path' => ['url' => $path, 'name' => $c],
                                       'key'  => 'ERROR_CODES',
                                       'line' => $line
                                   ];
                               }
                            }
                        }
                        return null;
                    }, $content);
                $line++;
            }
        }
        return ['warnings' => $warning,'dupes' => $dupes];
    }

}
