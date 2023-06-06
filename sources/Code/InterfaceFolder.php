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
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use IPS\toolbox\Code\Abstracts\ParserAbstract;

use function defined;
use function header;
use function str_replace;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

class _InterfaceFolder extends ParserAbstract
{

    protected $finder;

    /**
     * @inheritdoc
     */
    public function check(): array
    {
        $warning = [];
        /** @var SplFileInfo $file */
        foreach ($this->files as $file) {
            if ($file->isFile()) {
                $warning[] = [
                    'path' => [
                        'url'  => $this->buildPath($file->getPathname(), 0),
                        'name' => str_replace(
                            $this->app->getApplicationPath() . '/',
                            '',
                            $file->getPathname()
                        )
                    ],
                ];
            }
        }
        return $warning;
    }


}
