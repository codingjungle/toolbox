<?php

/**
 * @brief       TraitGenerator Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox
 * @since       4.0.0
 * @version     -storm_version-
 */


namespace Generator\Builders;

use Generator\Builders\Traits\ClassMethods;
use Generator\Builders\Traits\Constants;
use Generator\Builders\Traits\Imports;
use Generator\Builders\Traits\Properties;

/**
 * Class TraitGenerator
 *
 * @package IPS\toolbox\Generator\Builders
 * @mixin TraitGenerator
 */
class TraitGenerator extends GeneratorAbstract
{

    use ClassMethods;
    use Constants;
    use Imports;
    use Properties;

    /**
     * class type, final/abstract
     *
     * @var string
     */
    protected $type;

    protected $doImports = true;

    protected function writeBody()
    {
        $this->writeConst();
        $this->writeProperties();
        $this->writeMethods();
        $this->output("\n}");
    }

    public function writeSourceType()
    {
        $this->output("\ntrait {$this->className}");
        $this->output("\n{\n");
    }

}
