<?php

/**
 * @brief       Form Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox: Dev Center Plus
 * @since       1.3.0
 * @version     -storm_version-
 */

namespace IPS\toolbox\DevCenter\Sources\Generator;

use InvalidArgumentException;
use IPS\Helpers\Form\FormAbstract;
use IPS\Helpers\Form\Matrix;
use IPS\Helpers\Form\Text;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Log;
use IPS\Login;
use IPS\Member;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use IPS\toolbox\Application;
use UnexpectedValueException;

use function header;



class _Form extends GeneratorAbstract
{
    protected $includeConstructor = false;

    public function bodyGenerator()
    {
        $this->brief = 'Class';
        $this->generator->addImport(InvalidArgumentException::class);
        $this->generator->addImport(\IPS\Content\Item::class);
        $this->generator->addImport(FormAbstract::class);
        $this->generator->addImport(Matrix::class);
        $this->generator->addImport(Url::class);
        $this->generator->addImport(Lang::class);
        $this->generator->addImport(Log::class);
        $this->generator->addImport(Login::class);
        $this->generator->addImport(Request::class);
        $this->generator->addImport(Session::class);
        $this->generator->addImport(Theme::class);
        $this->generator->addImport(UnexpectedValueException::class);
        $this->generator->addImport(Member::class);

        $elementClass = 'IPS\\'.$this->app.'\\'.$this->classname.'\\Element';
        $this->generator->addImport($elementClass);

        $this->generator->addImportFunction('array_merge');
        $this->generator->addImportFunction('class_exists');
        $this->generator->addImportFunction('count');
        $this->generator->addImportFunction('defined');
        $this->generator->addImportFunction('header');
        $this->generator->addImportFunction('in_array');
        $this->generator->addImportFunction('is_array');
        $this->generator->addImportFunction('is_object');
        $this->generator->addImportFunction('json_encode');
        $this->generator->addImportFunction('mb_strlen');
        $this->generator->addImportFunction('mb_strpos');
        $this->generator->addImportFunction('mb_substr');
        $this->generator->addImportFunction('property_exists');
        $this->generator->addImportFunction('sha1');
        $this->generator->addImportFunction('str_replace');
        $this->generator->addImportFunction('func_get_args');

        $this->generator->addClassBody(\file_get_contents(Application::getRootPath('toolbox').'/applications/toolbox/data/defaults/form.txt') );
    }
}
