<?php

/**
 * @brief       Element Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  toolbox
 * @since       1.0.0
 * @version     -storm_version-
 */


namespace IPS\toolbox\Form;

use InvalidArgumentException;
use IPS\File;
use IPS\Helpers\Form\Address;
use IPS\Helpers\Form\Captcha;
use IPS\Helpers\Form\Checkbox;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Codemirror;
use IPS\Helpers\Form\Color;
use IPS\Helpers\Form\Custom;
use IPS\Helpers\Form\Date;
use IPS\Helpers\Form\DateRange;
use IPS\Helpers\Form\Editor;
use IPS\Helpers\Form\Email;
use IPS\Helpers\Form\FormAbstract;
use IPS\Helpers\Form\Ftp;
use IPS\Helpers\Form\Interval;
use IPS\Helpers\Form\Item;
use IPS\Helpers\Form\KeyValue;
use IPS\Helpers\Form\Matrix;
use IPS\Helpers\Form\Member;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Password;
use IPS\Helpers\Form\Poll;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Rating;
use IPS\Helpers\Form\Search;
use IPS\Helpers\Form\SocialGroup;
use IPS\Helpers\Form\Sort;
use IPS\Helpers\Form\Stack;
use IPS\Helpers\Form\Tel;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\TextArea;
use IPS\Helpers\Form\Timezone;
use IPS\Helpers\Form\Translatable;
use IPS\Helpers\Form\Upload;
use IPS\Helpers\Form\Url;
use IPS\Helpers\Form\WidthHeight;
use IPS\Helpers\Form\YesNo;
use IPS\Helpers\Form\Select;

use function array_merge;
use function array_pop;
use function defined;
use function explode;
use function header;
use function is_array;
use function mb_strtolower;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}
/**
 * Class _Element
 *
 * @package Forms
 * @mixin  Element
 * @property-read string $name
 * @property-read string $type
 * @property-read string|int|array $value
 * @property-read bool $required
 * @property-read array $options
 * @property-read callable $validationCallback
 * @property-read string $prefix
 * @property-read string $suffix
 * @property-read string $id
 * @property-read string $tab
 * @property-read bool $skip
 * @property-read string $header
 * @property-read bool $appearRequired
 * @property-read array $toggles
 * @property-read array $label
 * @property-read array $description
 * @property-read array $extra
 * @property-read string $sidebar
 * @property-read FormAbstract|string|null $class
 * @property-read bool $custom
 *
 */
class _Element
{

    /**
     * @var array
     */
    public static $helpers = [
        'address'      => Address::class,
        'addy'         => Address::class,
        'captcha'      => Captcha::class,
        'checkbox'     => Checkbox::class,
        'cb'           => Checkbox::class,
        'checkboxset'  => CheckboxSet::class,
        'cbs'          => CheckboxSet::class,
        'codemirror'   => Codemirror::class,
        'cm'           => Codemirror::class,
        'color'        => Color::class,
        'custom'       => Custom::class,
        'cs'           => Custom::class,
        'date'         => Date::class,
        'daterange'    => DateRange::class,
        'dr'           => DateRange::class,
        'editor'       => Editor::class,
        'email'        => Email::class,
        'file'         => File::class,
        'ftp'          => Ftp::class,
        'interval'     => Interval::class,
        'item'         => Item::class,
        'keyvalue'     => KeyValue::class,
        'kv'           => KeyValue::class,
        'matrix'       => Matrix::class,
        'member'       => Member::class,
        'node'         => Node::class,
        'number'       => Number::class,
        'num'          => Number::class,
        '#'            => Number::class,
        'password'     => Password::class,
        'pw'           => Password::class,
        'poll'         => Poll::class,
        'radio'        => Radio::class,
        'rating'       => Rating::class,
        'search'       => Search::class,
        'select'       => Select::class,
        'socialgroup'  => SocialGroup::class,
        'sg'           => SocialGroup::class,
        'sort'         => Sort::class,
        'stack'        => Stack::class,
        'Telephone'    => Tel::class,
        'tel'          => Tel::class,
        'text'         => Text::class,
        'textarea'     => TextArea::class,
        'ta'           => TextArea::class,
        'timezone'     => Timezone::class,
        'translatable' => Translatable::class,
        'trans'        => Translatable::class,
        'upload'       => Upload::class,
        'up'           => Upload::class,
        'url'          => Url::class,
        'widthheight'  => WidthHeight::class,
        'wh'           => WidthHeight::class,
        'yn'           => YesNo::class
    ];

    public static $nonHelpers = [
        'sidebar'   => 1,
        'header'    => 1,
        'separator' => 1,
        'message'   => 1,
        'tab'       => 1,
        'dummy'     => 1,
        'html'      => 1,
        'hidden'    => 1,
        'custom'    => 1,
    ];

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $type;

    /**
     * @var string|int|array
     */
    public $value;

    /**
     * @var bool
     */
    public $required = false;

    /**
     * @var array
     */
    public $options = [];

    /**
     * @var callable
     */
    public $validationCallback;

    /**
     * @var string
     */
    public $prefix;

    /**
     * @var string
     */
    public $suffix;

    /**
     * @var string
     */
    public $id;

    /**
     * @var string
     */
    public $tab;

    /**
     * @var bool
     */
    public $skip = false;

    /**
     * @var string
     */
    public $header;

    /**
     * @var bool
     */
    public $appearRequired;

    /**
     * @var array
     */
    public $label;

    /**
     * @var array
     */
    public $description;

    /**
     * @var array
     */
    public $toggles = [];

    /**
     * @var array
     */
    public $extra = [];

    /**
     * @var string
     */
    public $sidebar;

    /**
     * @var FormAbstract|string|null
     */
    public $class;

    /**
     * @var bool
     */
    public $custom = false;

    /**
     * @var null|string
     */
    public $empty;

    public $append;

    public $rowClasses = [];

    /**
     * FormAbstract constructor.
     *
     * @param string $name
     * @param string $type
     * @param string $custom
     */
    public function __construct($name, string $type, string $custom = '')
    {
        $class = null;
        $type = mb_strtolower($type);

        if ($name instanceof FormAbstract) {
            $class = $name;
            $type = 'helper';
        } elseif (!isset(static::$nonHelpers[$type])) {
            if (!($name instanceof FormAbstract) && static::isHelper($type) === true) {
                $class = static::getHelper($type) ?? Text::class;
                $type = 'helper';
            }

            if ($name instanceof FormAbstract) {
                $class = $name;
                $type = 'helper';
            }
        } elseif ($type === 'custom') {
            $class = $custom;
            $type = 'helper';
            $this->custom = true;
        }

        $this->name = $name;
        $this->type = $type;
        $this->class = $class;
    }

    public static function isHelper($type)
    {
        return isset(static::$helpers[$type]);
    }

    public static function getHelper($type)
    {
        return static::$helpers[$type] ?? null;
    }

    public function changeType(string $type, $custom = '')
    {
        if (!isset(static::$nonHelpers[$type])) {
            if (!($this->name instanceof FormAbstract) && isset(static::$helpers[$type])) {
                $this->class = '\\IPS\\Helpers\\Form\\' . static::$helpers[$type] ?? 'Text';
                $this->type = 'helper';
            } elseif ($this->name instanceof FormAbstract) {
                $this->class = $this->name;
                $this->type = 'helper';
            }
        } elseif ($type === 'custom') {
            $this->class = $custom;
            $this->type = 'helper';
            $this->custom = true;
        }

        return $this;
    }

    /**
     * @param $value
     *
     * @return Element
     */
    public function value($value): self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return \IPS\formularize\Form\_Element
     */
    public function required(bool $required = true): self
    {
        $this->required = $required;

        return $this;
    }

    /**
     * @param array $options
     *
     * @return self
     */
    public function options(array $options): self
    {
        if (isset($options['toggles'], $options['togglesOff'], $options['togglesOn'])) {
            throw new InvalidArgumentException(
                'Your options array contains toggles/togglesOn/togglesOff, use the toggles() method instead'
            );
        }
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    public function disabled(bool $disabled)
    {
        $this->options = array_merge($this->options, ['disabled' => $disabled]);

        return $this;
    }

    /**
     * @param $validation
     *
     * @return self
     */
    public function validation(callable $validation): self
    {
        $this->validationCallback = $validation;

        return $this;
    }

    /**
     * @param $prefix
     *
     * @return self
     */
    public function prefix(?string $prefix): self
    {
        if ($prefix !== null) {
            $this->prefix = $prefix;
        }
        return $this;
    }

    /**
     * @param $suffix
     *
     * @return self
     */
    public function suffix(?string $suffix): self
    {
        if ($suffix !== null) {
            $this->suffix = $suffix;
        }
        return $this;
    }

    public function append(string $append)
    {
        $this->append = $append;
        return $this;
    }

    /**
     * @param $id
     *
     * @return self
     */
    public function id(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @param $tab
     *
     * @return self
     */
    public function tab(string $tab): self
    {
        $this->tab = $tab;

        return $this;
    }

    /**
     * @return self
     */
    public function skip(): self
    {
        $this->skip = true;

        return $this;
    }

    /**
     * @param $header
     *
     * @return self
     */
    public function header(string $header): self
    {
        $this->header = $header;

        return $this;
    }

    /**
     * @param bool $off
     *
     * @return self
     */
    public function appearRequired(bool $off = false): self
    {
        $this->appearRequired = $off ? false : true;

        return $this;
    }

    /**
     * @param string $label
     *
     * @param array $sprintf
     *
     * @return self
     */
    public function label(string $label, array $sprintf = []): self
    {
        $this->label = [
            'key'     => $label,
            'sprintf' => $sprintf,
        ];

        return $this;
    }

    /**
     * @param string $description
     *
     * @param array $sprintf
     *
     * @return self
     */
    public function description(?string $description, array $sprintf = []): self
    {
        $this->description = [
            'key'     => $description,
            'sprintf' => $sprintf,
        ];

        return $this;
    }

    /**
     * @param array $toggles
     * @param bool $off
     * @param bool $na
     *
     * @return self
     */
    public function toggles(array $toggles, bool $off = false, bool $na = false): self
    {
        $key = 'togglesOff';
        $class = explode('\\', $this->class);
        $class = is_array($class) ? array_pop($class) : null;
        if ($off === false) {
            $key = 'toggles';
            $togglesOn = [
                'Checkbox' => 1,
                'YesNo'    => 1,
            ];

            if (isset($togglesOn[$class])) {
                $key = 'togglesOn';
            }
            if ($class === Node::class) {
                $key = 'toggleIds';
            }
            if ($class === Interval::class) {
                $key = 'valueToggles';
            }
        } elseif ($class === Node::class) {
            $key = 'toggleIdsOff';
        }

        if ($na === true) {
            $key = 'na' . $key;
        }

        $this->toggles[] = [
            'key'      => $key,
            'elements' => $toggles,
        ];

        return $this;
    }

    /**
     * @param array $extra
     *
     * @return self
     */
    public function extra(array $extra): self
    {
        $this->extra = $extra;

        return $this;
    }

    /**
     * @param string $sidebar
     *
     * @return self
     */
    public function sidebar(string $sidebar): self
    {
        $this->sidebar = $sidebar;

        return $this;
    }

    /**
     * @param $empty
     *
     * @return self
     */
    public function empty($empty): self
    {
        $this->empty = $empty;

        return $this;
    }

    public function rowClass($class)
    {
        if ($class !== null) {
            if (is_array($class)) {
                $this->rowClasses = array_merge($this->rowClasses, $class);
            } else {
                $this->rowClasses[] = $class;
            }
        }
        return $this;
    }
}
