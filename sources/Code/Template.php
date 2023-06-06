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
use IPS\toolbox\ReservedWords;
use Symfony\Component\Finder\Finder;
use IPS\toolbox\Code\Abstracts\ParserAbstract;

use function defined;
use function header;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

class _Template extends ParserAbstract
{

    protected $warnings;

    protected $finder;

    /**
     * Compares the name to a list of all php function names to ensure that the template name can be used
     *
     * @param $name
     *
     * @return bool
     */
    public static function validateName($name): bool
    {
        return ReservedWords::check($name);
    }

    /**
     * @inheritdoc
     */
    public function verify(): array
    {
        foreach ($this->finder as $invalidTemplate) {
            $this->warnings[] = $invalidTemplate->getPath();
        }
        return $this->warnings;
    }

}
