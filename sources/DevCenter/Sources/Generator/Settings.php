<?php

/**
 * @brief       Settings Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox: Dev Center Plus
 * @since       1.3.0
 * @version     -storm_version-
 */

namespace IPS\toolbox\DevCenter\Sources\Generator;

use IPS\Settings;
use IPS\toolbox\Application;
use Throwable;
use UnderflowException;


class _Settings extends GeneratorAbstract
{
    protected $includeConstructor = false;

    public function bodyGenerator()
    {
        $this->brief = 'Class';
        $this->generator->addImport(UnderflowException::class);
        $this->generator->addImport(Throwable::class);
        $this->generator->addImportFunction('array_combine');
        $this->generator->addImportFunction('array_values');
        $this->generator->addImportFunction('is_array');
        $this->generator->addImportFunction('defined');
        $this->generator->addImportFunction('header');
        $this->generator->addImportFunction('json_decode');

        $this->generator->addClassBody(\file_get_contents(Application::getRootPath('toolbox').'/applications/toolbox/data/defaults/settings.txt') );
        $this->generator->addExtends(Settings::class,false);
    }
}
