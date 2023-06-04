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

use IPS\Data\Store;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use IPS\Db;

use function defined;
use function explode;
use function header;
use function mb_strpos;
use function preg_match_all;


if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

class _Todo extends ParserAbstract
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
        try {
            Db::i()->delete('toolbox_todo', ['todo_app=?', $this->app]);
        }catch(\Throwable $e){}
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

                preg_match('#@todo (.*?)$#uim', $content, $matches);

                if (empty($matches[1]) === false) {
                    _p($matches[1]);
                    $db = (new \IPS\toolbox\Code\Utils\Todo());

                    $warning[] = [
                        'path' => ['url' => $path, 'name' => $name],
                        'key'  => $matches[1],
                        'line' => $line
                    ];
                }
                $line++;
            }
        }
        return $warning;
    }
}
