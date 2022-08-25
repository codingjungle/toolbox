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

use Exception;
use InvalidArgumentException;
use IPS\toolbox\Proxy\Proxyclass;
use IPS\Helpers\Form\FormAbstract;
use IPS\Helpers\Form\Matrix;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Log;
use IPS\Login;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use IPS\toolbox\Application;
use UnexpectedValueException;

use Laminas\Code\Reflection\ClassReflection;

use function trim;
use function ltrim;
use function rtrim;
use function str_replace;

class _Form extends GeneratorAbstract
{
    protected $includeConstructor = false;

    public function bodyGenerator()
    {
        $vals = [
            'type' => 'Element',
            'className' => 'Element',
            'namespace' => 'Form',
        ];
        $eclass = new Elements($vals, $this->application);
        $eclass->process();

        $this->brief = 'Class';
        $this->generator->addImport(Log::class);
        $this->generator->addImport(Exception::class);
        $this->generator->addImport(Login::class);
        $this->generator->addImport(Theme::class);
        $this->generator->addImport(Member::class);
        $this->generator->addImport(Request::class);
        $this->generator->addImport(Session::class);
        $this->generator->addImport(Url::class);
        $this->generator->addImport(\IPS\Content\Item::class);
        $this->generator->addImport(\IPS\Helpers\Form\Radio::class);
        $this->generator->addImport(\IPS\Helpers\Form\Matrix::class);
        $this->generator->addImport(InvalidArgumentException::class);
        $elementClass = 'IPS\\' . $this->app . '\\' . $this->classname . '\\Element';
        $this->generator->addImport($elementClass);
        $this->generator->addImport(UnexpectedValueException::class);
        $this->generator->addImport(\IPS\Helpers\Form\CheckboxSet::class);
        $this->generator->addImport(FormAbstract::class);

        $this->generator->addImportFunction('sha1');
        $this->generator->addImportFunction('count');
        $this->generator->addImportFunction('header');
        $this->generator->addImportFunction('uniqid');
        $this->generator->addImportFunction('defined');
        $this->generator->addImportFunction('implode');
        $this->generator->addImportFunction('shuffle');
        $this->generator->addImportFunction('explode');
        $this->generator->addImportFunction('is_array');
        $this->generator->addImportFunction('array_map');
        $this->generator->addImportFunction('is_object');
        $this->generator->addImportFunction('mb_strlen');
        $this->generator->addImportFunction('mb_strpos');
        $this->generator->addImportFunction('mb_substr');
        $this->generator->addImportFunction('array_keys');
        $this->generator->addImportFunction('array_merge');
        $this->generator->addImportFunction('json_encode');
        $this->generator->addImportFunction('str_replace');
        $this->generator->addImportFunction('array_values');
        $this->generator->addImportFunction('class_exists');
        $this->generator->addImportFunction('array_combine');
        $this->generator->addImportFunction('func_get_args');
        $this->generator->addImportFunction('property_exists');
        $this->generator->addImportFunction('array_key_exists');

        $this->generator->addImportConstant('null');

        $appPath = $this->application->getApplicationPath() . '/dev/';
        $basePath = Application::getRootPath('toolbox') . '/applications/toolbox/data/defaults/sources/';
        $app = $this->application->directory;
        //'fforms', 'toolbox',
        //'#app#cjforms', '#app#',
        $find = ['fforms','toolbox'];
        $replacements = ['cjforms',$app];

        //build body of form
        $code = (new ClassReflection(\IPS\toolbox\Form::class))->getParentClass();
        $content = $code->getContents(false);
        $content = trim($content);
        $content = ltrim($content,'{');
        $content = rtrim($content,"}");
        $content = trim($content);
        $content = str_replace($find, $replacements, $content);
        $this->generator->addClassBody($content);
        $find = ['#app#'];
        $replacements = [$this->application->directory];
        //do css
        $path = $basePath . 'cjform.css';
        $content = \file_get_contents($path);
        $content = str_replace($find, $replacements, $content);
        $cssDir = $appPath . '/css/global/';
        $this->_writeFile('cjform.css', $content, $cssDir);

        //do template
        $path = $basePath . 'header.phtml';
        $content = \file_get_contents($path);
        $content = str_replace($find, $replacements, $content);
        $cssDir = $appPath . '/html/global/' . $this->application->directory . 'cjforms/';
        $this->_writeFile('header.phtml', $content, $cssDir);


    }
}
