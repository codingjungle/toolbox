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

use InvalidArgumentException;
use IPS\IPS;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use IPS\toolbox\Code\Abstracts\ParserAbstract;

use function array_shift;
use function defined;
use function header;
use function json_decode;
use function json_encode;
use function mb_strlen;
use function mb_substr;
use function str_replace;
use function trim;

use const JSON_PRETTY_PRINT;


if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

class _Db extends ParserAbstract
{
    protected $warnings;
    protected $finder;

    /**
     * @inheritdoc
     */
    public function check(): array
    {
        $ipsApps = IPS::$ipsApps;
        $warning = [];
        /** @var SplFileInfo $file */
        foreach ($this->files as $file) {
            $queries = json_decode($file->getContents(), true);
            if ($queries) {
                foreach ($queries as $query) {
                    if (isset($query['method']) && $query['method'] === 'addColumn') {
                        $params = $query['params'];
                        $table = array_shift($params);
                        $definition = $params;
                        foreach ($ipsApps as $app) {
                            $tt = mb_substr($table, 0, mb_strlen($app));
                            if ($tt === $app) {
                                $warning[] = [
                                    'path'  => [
                                        'url'  => $this->buildPath($file->getPathname(), 0),
                                        'name' => str_replace(
                                            $this->app->getApplicationPath() . '/',
                                            '',
                                            $file->getPathname()
                                        )
                                    ],
                                    'app'   => $app,
                                    'table' => $table,
                                    'pre'   => trim(json_encode($definition, JSON_PRETTY_PRINT))
                                ];
                            }
                        }
                    }
                }
            }
        }
        return $warning;
    }


}
