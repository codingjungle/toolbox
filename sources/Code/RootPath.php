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
use IPS\toolbox\Code\Abstracts\ParserAbstract;

use function defined;
use function explode;
use function header;
use function mb_strpos;
use function preg_match_all;


if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

class _RootPath extends ParserAbstract
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

                preg_match_all('#ROOT_PATH#u', $content, $matches);

                if (mb_strpos($content, 'ROOT_PATH') !== false) {
                    $warning[] = [
                        'path' => ['url' => $path, 'name' => $name],
                        'key'  => '\\IPS\\ROOT_PATH',
                        'line' => $line
                    ];
                }
                $line++;
            }
        }
        return $warning;
    }

}
