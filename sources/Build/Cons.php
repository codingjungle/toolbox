<?php
/**
 * @brief      Cons Singleton
 * @copyright  -storm_copyright-
 * @package    IPS Social Suite
 * @subpackage toolbox
 * @since      -storm_since_version-
 * @version    -storm_version-
 */

namespace IPS\toolbox\Build;

use IPS\Application;
use IPS\IPS;
use IPS\Member;
use IPS\Output;
use IPS\Patterns\Singleton;
use IPS\Request;
use IPS\toolbox\Form;

use function _p;
use function md5;
use function time;
use function random_int;
use function array_merge;
use function constant;
use function defined;
use function file_put_contents;
use function function_exists;
use function gettype;
use function header;
use function implode;
use function in_array;
use function is_array;
use function ksort;
use function mb_substr;
use function mb_ucfirst;
use function opcache_reset;
use function sleep;

use const IPS\NO_WRITES;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * Cons Class
 *
 * @mixin Cons
 */
class _Cons extends Singleton
{

    /**
     * @brief Singleton Instances
     * @note  This needs to be declared in any child class.
     * @var static
     */
    protected static $instance;

    protected static $importantIPS = [];

    protected static $devTools = [];

    protected $constants;

    public function form()
    {
        $constants = $this->buildConstants();
        $form = Form::create();
        foreach ($constants as $key => $value) {
            $tab = $value['tab'];
            Member::loggedIn()->language()->words[$tab . '_tab'] = $tab;
            if ($key === 'CACHEBUST_KEY') {
                $value['current'] = 'md5(random_int(0, 10000) . time())';
            }
            $v = $value['current'];
            $form->addElement($key)->label($key)->value($v)->description($value['description'] ?? '')->tab(
                $tab
            );

            switch ($value['type']) {
                case 'yn':
                case 'boolean':
                    $form->getElement($key)->changeType('yn')->empty((bool)$value['current']);
                    break;
                case 'integer':
                case 'int':
                    $form->getElement($key)->changeType('number');
                    break;
                case 'url':
                    $form->getElement($key)->changeType('url');
                    break;
                case 'stack':
                case 'array':
                    $form->getElement($key)->changeType('stack');
                    break;

            }
            if(isset($value['type']) && $value['type'] === 'stack'){
                $form->getElement($key)->changeType('stack');
            }
        }

        if ($values = $form->values()) {
            $this->save($values, $constants);
            Output::i()->redirect(Request::i()->url(), 'Constants.php Updated!');
        }

        return $form;
    }

    public function buildConstants()
    {
        if ($this->constants === null) {
            $cons = IPS::defaultConstants();
            $first = [];
            $constants = [];
            $important[] = static::$importantIPS;

            foreach (Application::allExtensions('toolbox', 'constants') as $extension) {
                $important[] = $extension->add2Important();
                $extra = $extension->getConstants();
                $first[] = $extra;

                foreach ($extra as $k => $v) {
                    static::$devTools[$k] = $v['name'];
                }
            }

            $first = array_merge(...$first);
            $importantIPS = array_merge(... $important);
            foreach ($cons as $key => $con) {
                if ($key === 'READ_WRITE_SEPARATION' || $key === 'REPORT_EXCEPTIONS') {
                    continue;
                }
                $current = constant('\\IPS\\' . $key);
                $data = [
                    'name'    => $key,
                    'default' => $con,
                    'current' => $current,
                    'type'    => gettype(constant('\\IPS\\' . $key)),
                    'tab' => \in_array($key, $importantIPS) ? 'Important' : mb_ucfirst(mb_substr($key, 0, 1))
                ];
                if($data['tab'] === 'Important'){
                    $first[$key] = $data;
                }
                else{
                    $constants[$key] = $data;
                }
            }
            ksort($constants);
            $this->constants = array_merge($first, $constants);
        }

        return $this->constants;
    }

    public function save(array $values, array $constants)
    {
        $cons = [];

        foreach (Application::allExtensions('toolbox', 'constants') as $extension) {
            $extension->formateValues($values);
        }
        foreach ($constants as $key => $val) {
            $data = $values[$key]??null;
            $data = str_replace(['\\'], ['\\\\'], $data);
            switch ($val['type']) {
                case 'integer':
                case 'boolean':
                    $check = (int)$data;
                    $check2 = (int)$val['default'];
                    break;
                default:
                    try {
                        if (is_array($data)) {
                            $check2 = $val['default'];
                            $check = $data;
                        } else {
                            $check2 = (string)$val['default'];
                            $check = (string)$data;
                        }
                    }catch(\Exception $e){
                        _p($key);
                    }
                    break;
            }
            if($key === 'CACHEBUST_KEY') {
                $check = md5(random_int(0, 10000) . time());
                $check2 = md5(random_int(0, 10000) . time());
            }
            if ($key === 'SUITE_UNIQUE_KEY') {
                $check2 = "\$versions = json_decode(file_get_contents(__DIR__ . '/applications/core/data/versions.json'), true);";
                $check2 .= "\nmb_substr(md5(array_pop(\$versions)), 10, 10)";
            }
            if ((defined('\\IPS\\' . $key) && $check !== $check2) || in_array($key, static::$devTools, true)) {
                $prefix = '';
                if ($key === 'SUITE_UNIQUE_KEY') {
                    $prefix = "\$versions = json_decode(file_get_contents(__DIR__ . '/applications/core/data/versions.json'), true);\n";
                    $dataType = "mb_substr(md5(array_pop(\$versions)), 10, 10)";
                } else {
                    switch ($val['type']) {
                        case 'yn':
                        case 'boolean':
                            $dataType = $data ? 'true' : 'false';
                            break;
                        case 'integer':
                        case 'int':
                            $dataType = (int)$data;
                            break;
                        case 'stack':
                        case 'array':
                            $dataType = var_export($data, true);
                            break;
                        default:
                            $dataType = "'" . $data . "'";
                            break;
                    }
                    if ($key === 'SUITE_UNIQUE_KEY') {
                        $dataType = $data;
                    }
                    if($key === 'CACHEBUST_KEY') {
                        $dataType = "'" .  md5(random_int(0, 10000) . time()) . "'";
                    }
                }
                //test test
                $cons[] = $prefix . "\\define('" . $key . "'," . $dataType . ');';
                if($key === 'DT_USE_WSL'){
                    $cons[] = $prefix . "\\define('DEV_WHOOPS_HANDLER', '\\IPS\\toolbox\Debug\\Handler');";
                }

            }
        }
        $toWrite = 'include __DIR__.\'/applications/toolbox/sources/Debug/Helpers.php\';' . "\n";

        $toWrite .= implode("\n", $cons);
        $fileData = <<<EOF
<?php
{$toWrite}
EOF;
        if (NO_WRITES !== true) {
            file_put_contents(\IPS\Application::getRootPath() . '/constants.php', $fileData);
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }
            sleep(2);
        }
    }
}

