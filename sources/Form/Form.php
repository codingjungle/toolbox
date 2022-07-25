<?php

/**
 * @brief       Form Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  toolbox
 * @since       1.0.0
 * @version     -storm_version-
 */


namespace IPS\toolbox;

use InvalidArgumentException;
use IPS\Content\Item;
use IPS\Helpers\Form\FormAbstract;
use IPS\Helpers\Form\Matrix;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Log;
use IPS\Login;
use IPS\Member;
use IPS\Request;
use IPS\Session;
use IPS\toolbox\Form\Element;
use IPS\Theme;
use UnexpectedValueException;

use function array_keys;
use function array_merge;
use function class_exists;
use function count;
use function defined;
use function func_get_args;
use function header;
use function implode;
use function in_array;
use function is_array;
use function is_object;
use function json_encode;
use function mb_strlen;
use function mb_strpos;
use function mb_substr;
use function property_exists;
use function sha1;
use function shuffle;
use function str_replace;
use function array_key_exists;

use const null;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * Form Class
 * @mixin Form
 */
class _Form extends \IPS\Helpers\Form
{

    /**
     * @var
     */
    public $error;

    /**
     * @var \IPS\Helpers\Form
     */
    public $form;

    /**
     * @var string
     */
    public $id = 'default';
    public $activeTab;
    public $builder = false;
    public $item = null;
    public $container = null;
    public $hasSense = false;
    public $threshold = false;
    public $includeItem = false;
    /**
     * @var array
     */
    protected $elementStore = [];
    /**
     * @var object
     */
    protected $object;
    /**
     * @var array
     */
    protected $bitOptions;
    /**
     * @var string
     */
    protected $formPrefix;
    /**
     * @var Lang
     */
    protected $lang;
    /**
     * @var string
     */
    protected $header;
    /**
     * @var bool
     */
    protected $built = false;
    /**
     * @var array
     */
    protected $bitOptionsPrefixes = [];
    /**
     * @var bool
     */
    protected $stripPrefix = true;
    /**
     * @var bool
     */
    protected $suffix = true;
    protected $matrix = [];
    /**
     * @var bool
     */
    protected $addDbPrefix = true;
    protected $dialogForm = false;
    protected $doBitWise = true;
    protected $dbPrefix = true;
    protected $customTemplateData;
    protected $tabsToHeaders = false;
    protected $baseClass = 'formularizePopUpForm';
    protected $prefixTabs = true;
    protected $prefixHeaders = true;
    protected $createLangs = false;
    protected $togglesAppending = true;
    protected $customClasses = '';
    protected $tabStore = [];
    protected $headerStore = [];
    protected $buildElsStore = [];
    protected $on = [];
    protected $off = [];
    protected $wizard = false;
    protected $wizardEdit = false;
    protected $random = false;

    /**
     * Form constructor.
     *
     * @param \IPS\Helpers\Form|null $form
     */
    public function __construct(
        $id = 'form',
        $submitLang = 'save',
        $action = null,
        $attributes = [],
        \IPS\Helpers\Form $form = null
    ) {
        parent::__construct($id, $submitLang, $action, $attributes);
        if ($form === null) {
            $this->form = new \IPS\Helpers\Form();
        } elseif ($form instanceof \IPS\Helpers\Form) {
            $this->form = $form;
        }
    }

    /**
     * @param \IPS\Helpers\Form|Form|null $form
     *
     * @return Form
     */
    public static function create(
        \IPS\Helpers\Form $form = null,
        $id = 'form',
        $submitLang = 'save',
        $action = null,
        $attributes = []
    ): self {
        return new static($id, $submitLang, $action, $attributes, $form);
    }

    public function clearBaseClass()
    {
        $this->baseClass = '';
        return $this;
    }

    public function makeWizard(bool $wizard)
    {
        $this->wizard = $wizard;
        return $this;
    }

    public function editWizard(bool $edit)
    {
        $this->wizardEdit = $edit;
        return $this;
    }

    public function builder()
    {
        $this->prefixHeaders = false;
        $this->prefixTabs = false;
        $this->createLangs = true;
        $this->togglesAppending = false;
        return $this;
    }

    public function tabsToHeaders(bool $tabsToHeaders = true)
    {
        $this->tabsToHeaders = $tabsToHeaders;
    }

    public function dialogForm()
    {
        //$this->dialogForm = true;
        $this->formClass('PopUpForm ipsClearfix');
        return $this;
    }

    /**
     * @param $class
     *
     * @return \IPS\formularize\_Form
     */
    public function formClass($class): self
    {
        $this->customClasses .= ' ' . $class;
        $this->form->class = $this->customClasses;
        return $this;
    }

    /**
     * @param $prefix
     *
     * @return self
     */
    public function formPrefix($prefix): self
    {
        $this->formPrefix = $prefix;

        return $this;
    }

    /**
     * @param array $bitOptions
     *
     * @return self
     */
    public function bitOptions(array $bitOptions): self
    {
        $this->bitOptions = $bitOptions;

        return $this;
    }

    /**
     * @param $object
     *
     * @return self
     */
    public function object($object): self
    {
        if (is_object($object)) {
            $this->object = $object;
            if ($this->formPrefix === null && property_exists($object, 'formLangPrefix')) {
                $this->formPrefix = $object::$formLangPrefix;
            }
        }
        return $this;
    }

    /**
     * @param $id
     *
     * @return self
     */
    public function formId($id): self
    {
        if (isset($id['value'])) {
            $id = $id['value'];
        }
        $this->form->id = $id;
        $this->attributes(['id' => $id]);
        return $this;
    }

    /**
     * @param array $attributes
     *
     * @return self
     */
    public function attributes(array $attributes): self
    {
        $this->form->attributes = array_merge($this->form->attributes, $attributes);

        return $this;
    }

    /**
     * @param $action
     *
     * @return self
     */
    public function action($action): self
    {
        $this->form->action = $action;

        return $this;
    }

    /**
     * @param $langKey
     *
     * @return self
     * @throws UnexpectedValueException
     */
    public function submitLang($langKey, $disabled = false, $id = null): self
    {
        if ($langKey !== null) {
            $attributes = [
                'tabindex'  => '2',
                'accesskey' => 's',
            ];
            if ($disabled === true) {
                $attributes['disabled'] = true;
            }
            if ($id !== null) {
                $attributes['id'] = $id . '_button';
            }
            $this->form->actionButtons[0] = Theme::i()->getTemplate('forms', 'core', 'global')->button(
                $langKey,
                'submit',
                null,
                'ipsButton ipsButton_primary',
                $attributes
            );
        } else {
            unset($this->form->actionButtons[0]);
        }

        return $this;
    }

    /**
     * @param FormAbstract $helper
     *
     * @return self
     */
    public function addHelper(FormAbstract $helper): self
    {
        $this->elementStore[] = $helper;

        return $this;
    }

    public function noSuffix()
    {
        $this->suffix = false;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->build();
    }

    /**
     * @return \IPS\Helpers\Form|string|array
     */
    public function build()
    {
        $this->built = true;
        if ($this->error !== null) {
            $this->form->error = $this->error;
        }
        $typesWName = [
            'tab',
            'header',
            'sidebar',
            'helper',
            'dummy',
            'matrix',
            'hidden',
            'message',
        ];
        $lastTab = null;
        $noForm = [];
        /** @var Element $el */
        foreach ($this->elementStore as $el) {
            if ($el instanceof FormAbstract) {
                $this->form->addElement($el);
                continue;
            }
            if (is_array($el) && isset($el['matrix']))
            {
                $type = 'matrix';
            }
            elseif (!($el instanceof Element)) {
                continue;
            }
            else {
                $type = $el->type ?? 'helper';
                $name = null;
                $plain = '';
                $extra = $el->extra ?? [];
                $default = $el->value;
                $id = $el->id;

                if (in_array($type, $typesWName, true)) {
                    $skip = $el->skip;
                    $plain = $el->name;
                    $name = $skip ? '' : $this->formPrefix ?? '';
                    $name .= $plain;
                }

                if ($id === null) {
                    $id = 'js_' . $name;
                }

                if ($el->tab !== null) {
                    $suffix = $this->suffix === true ? '_tab' : '';
                    $prefix = $this->prefixTabs === true ? $this->formPrefix : '';
                    $tab = $prefix . $el->tab . $suffix;
                    $lastTab = $tab;
                    if (!isset($this->tabStore[$tab])) {
                        $this->tabStore[$tab] = 1;
                        if ($this->createLangs === true) {
                            $key = Url::seoTitle($tab);
                            Member::loggedIn()->language()->words[$key] = $tab;
                            $tab = $key;
                        }
                        if ($this->tabsToHeaders === false) {
                            $this->form->addTab($tab);
                        } else {
                            $this->form->_insert(
                                Theme::i()->getTemplate('fforms', 'stratagem', 'front')->header(
                                    $tab,
                                    "{$this->form->id}_header_{$tab}"
                                )
                            );                        }
                    }
                }

                if ($el->header !== null) {
                    $suffix = $this->suffix === true ? '_header' : '';
                    $prefix = $this->prefixTabs === true ? $this->formPrefix : '';
                    $header = $prefix . $el->header . $suffix;
                    if (!isset($this->headerStore[$header])) {
                        $this->headerStore[$header] = 1;
                        if ($this->createLangs === true) {
                            $key = Url::seoTitle($header);
                            Member::loggedIn()->language()->words[$key] = $header;
                            $header = $key;
                        }
                        $this->form->_insert(
                            Theme::i()->getTemplate('fforms', 'stratagem', 'front')->header(
                                $header,
                                "{$this->form->id}_header_{$key}"
                            )
                        );
                    }
                }

                if ($el->sidebar) {
                    $suffix = $this->suffix ? '_sidebar' : '';
                    $sideBar = $this->formPrefix . $el->sidebar . $suffix;
                    if (Member::loggedIn()->language()->checkKeyExists($sideBar)) {
                        $sideBar = Member::loggedIn()->language()->addToStack($sideBar);
                    }
                    $this->form->addSidebar($sideBar);
                }
            }

            switch ($type) {
                case 'tab':
                    $suffix = $this->suffix === true ? '_tab' : '';
                    $prefix = $this->prefixTabs === true ? $this->formPrefix : '';
                    $tab = $prefix . $el->name . $suffix;
                    $lastTab = $tab;
                    if (!isset($this->tabStore[$tab])) {
                        $this->tabStore[$tab] = 1;
                        if ($this->createLangs === true) {
                            $key = Url::seoTitle($tab);
                            Member::loggedIn()->language()->words[$key] = $tab;
                            $tab = $key;
                        }
                        if ($this->tabsToHeaders === false) {
                            $this->form->addTab($tab, $el->options['icon'] ?? null, null,
                                $el->options['css'] ?? null );
                        } else {
                            $this->form->_insert(
                                Theme::i()->getTemplate('fforms', 'stratagem', 'front')->header(
                                    $tab,
                                    "{$this->form->id}_header_{$tab}"
                                )
                            );
                        }
                    }
                    break;
                case 'header':
                    $suffix = $this->suffix === true ? '_header' : '';
                    $prefix = $this->prefixTabs === true ? $this->formPrefix : '';
                    $header = $prefix . $el->name . $suffix;
                    if (!isset($this->headerStore[$header])) {
                        $this->headerStore[$header] = 1;
                        if ($this->createLangs === true) {
                            $key = Url::seoTitle($header);
                            Member::loggedIn()->language()->words[$key] = $header;
                            $header = $key;
                        }
                        $this->form->_insert(
                            Theme::i()->getTemplate('fforms', 'stratagem', 'front')->header(
                                $header,
                                "{$this->form->id}_header_{$header}",
                                implode(' ', $el->rowClasses)
                            )
                        );
                    }
                    break;
                case 'sidebar':
                    if (Member::loggedIn()->language()->checkKeyExists($name)) {
                        $name = Member::loggedIn()->language()->addToStack($name);
                    }
                    $this->form->addSidebar($name);
                    break;
                case 'separator':
                    $this->form->addSeparator();
                    break;
                case 'message':
                    $parse = false;
                    if (Member::loggedIn()->language()->checkKeyExists($name)) {
                        $parse = true;
                        if (isset($extra['sprintf'])) {
                            $parse = false;
                            $sprintf = $extra['sprintf'];
                            $name = Member::loggedIn()->language()->addToStack($name, false, ['sprintf' => $sprintf]);
                        }
                    }
                    $css = $extra['css'] ?? '';
                    $this->form->addMessage($name, $css, $parse, $id);
                    break;
                case 'helper':
                    if (isset($this->elementsStore[$name])) {
                        continue 2;
                    }
                    $class = $el->class;
                    if ($class instanceof FormAbstract) {
                        $this->form->addElement($class);
                        break;
                    }

                    if (!class_exists($class, true)) {
                        Log::debug('invalid form class ' . $class);
                        continue 2;
                    }
                    $this->buildElsStore[$name] = 1;
                    $required = $el->required;
                    $options = $el->options;
                    $validation = $el->validationCallback;
                    $prefix = $el->prefix;
                    $suffix = $el->suffix;
                    $toggles = $el->toggles;

                    if ($default === null) {
                        $obj = $this->object;
                        $prop = $plain;
                        $prop2 = $this->formPrefix . $prop;
                        $prop3 = $el->id;

                        if (is_object($obj)) {
                            $default = $obj->{$prop} ?? $obj->{$prop2} ?? $obj->{$prop3} ?? null;
                        }
                        if ($default === null && empty($this->bitOptions) === false && is_object($this->object)) {
                            /* @var array $val */
                            foreach ($this->bitOptions as $bit => $val) {
                                foreach ($val as $k => $v) {
                                    if (!empty($obj->{$k}[$prop])) {
                                        $default = $obj->{$k}[$prop];
                                        break 2;
                                    }
                                    if (!empty($obj->{$k}[$prop2])) {
                                        $default = $obj->{$k}[$prop2];
                                        break 2;
                                    }
                                }
                            }
                        }
                    }

                    if (!isset($options['zeroVal']) && empty($default) === true && $el->empty !== null) {
                        $default = $el->empty;
                    }
                    elseif (isset($options['zeroVal']) && $default === 0) {
                        $default = 0;
                    }

                    /* @var array $toggles */
                    if (empty($toggles) !== true) {
                        foreach ($toggles as $toggle) {
                            if (isset($toggle['key'])) {
                                switch ($toggle['key']) {
                                    case 'toggles':
                                    case 'natoggles':
                                        foreach ($toggle['elements'] as $k => $val) {
                                            foreach ($val as $v) {
                                                if ($this->togglesAppending) {
                                                    $options['toggles'][$k][] = $toggle['key'] === 'toggles' ? 'js_' . $this->formPrefix . $v : $v;
                                                } else {
                                                    $options['toggles'][$k][] = str_replace('.', '_', $v);
                                                }
                                            }
                                        }
                                        break;
                                    case 'togglesOn':
                                    case 'natogglesOn':
                                        foreach ($toggle['elements'] as $val) {
                                            if ($this->togglesAppending) {
                                                $options['togglesOn'][] = $toggle['key'] === 'togglesOn' ? 'js_' . $this->formPrefix . $val : $val;
                                            } else {
                                                $options['togglesOn'][] = str_replace('.', '_', $val);
                                            }
                                        }
                                        break;
                                    case 'togglesOff':
                                    case 'natogglesOff':
                                        foreach ($toggle['elements'] as $val) {
                                            if ($this->togglesAppending) {
                                                $options['togglesOff'][] = $toggle['key'] === 'togglesOff' ? 'js_' . $this->formPrefix . $val : $val;
                                            } else {
                                                $options['togglesOff'][] = str_replace('.', '_', $val);
                                            }
                                        }
                                        break;
                                }
                            }
                        }
                    }

                    if (
                        is_array($options) &&
                        isset($options['options']) &&
                        isset($options['parse']) &&
                        $options['parse'] === 'lang' &&
                        isset($options['prefixLang']) &&
                        $options['prefixLang']
                    ) {
                        $langs = [];
                        foreach ($options['options'] as $key => $val) {
                            $langs[$key] = $this->formPrefix . $val . '_options';
                        }
                        $options['options'] = $langs;
                    }

                    if ($el->append !== null) {
                        $id .= $el->append;
                    }

                    if ($suffix && Member::loggedIn()->language()->checkKeyExists($suffix) === true) {
                        $suffix = Member::loggedIn()->language()->addToStack($suffix);
                    }

                    if ($prefix && Member::loggedIn()->language()->checkKeyExists($prefix) === true) {
                        $prefix = Member::loggedIn()->language()->addToStack($prefix);
                    }

//                    if ((int)Request::i()->timed === 1 || (int) Request::i()->refresh === 1) {
//                        $required = false;
//                        $validation = null;
//                    }

                    $element = new $class($name, $default, $required, $options, $validation, $prefix, $suffix, $id);

                    $element->rowClasses = $el->rowClasses;
                    if ($el->appearRequired === true) {
                        $element->appearRequired = $el->appearRequired;
                    }
                    if (is_array($el->label) && isset($el->label['key'])) {
                        $label = $el->label['key'];
                        if (Member::loggedIn()->language()->checkKeyExists($this->formPrefix . $label)) {
                            if (isset($el->label['sprintf']) && is_array($el->label['sprintf'])) {
                                $label = Member::loggedIn()->language()->addToStack(
                                    $this->formPrefix . $label,
                                    ['sprintf' => $el->label['sprintf']]
                                );
                            } else {
                                $label = Member::loggedIn()->language()->addToStack($this->formPrefix . $label);
                            }
                        }
                        if ($label === $el->label['key'] && Member::loggedIn()->language()->checkKeyExists($label)) {
                            if (isset($el->label['sprintf']) && is_array($el->label['sprintf'])) {
                                $label = Member::loggedIn()->language()->addToStack(
                                    $label,
                                    ['sprintf' => $el->label['sprintf']]
                                );
                            } else {
                                $label = Member::loggedIn()->language()->addToStack($label);
                            }
                        }
                        $element->label = $label;
                    }

                    if (is_array($el->description) && isset($el->description['key'])) {
                        $desc = $el->description['key'];
                        if (Member::loggedIn()->language()->checkKeyExists($this->formPrefix . $desc)) {
                            if (isset($el->description['sprintf'])) {
                                $desc = Member::loggedIn()->language()->addToStack(
                                    $this->formPrefix . $desc,
                                    false,
                                    ['sprintf' => $el->description['sprintf']]
                                );
                            } else {
                                $desc = Member::loggedIn()->language()->addToStack($this->formPrefix . $desc);
                            }
                        }
                        if ($desc === $el->description['key'] && Member::loggedIn()->language()->checkKeyExists(
                                $desc
                            )) {
                            if (isset($el->description['sprintf'])) {
                                $desc = Member::loggedIn()->language()->addToStack(
                                    $desc,
                                    false,
                                    ['sprintf' => $el->description['sprintf']]
                                );
                            } else {
                                $desc = Member::loggedIn()->language()->addToStack($desc);
                            }
                        }
                        Member::loggedIn()->language()->words[$name . '_desc'] = $desc;
                    }
                    $this->form->add($element);
                    break;
                case 'dummy':
                    $desc = '';
                    $warning = '';
                    if (is_array($el->description) && isset($el->description['key'])) {
                        $desc = $el->description['key'];
                        if (Member::loggedIn()->language()->checkKeyExists($this->formPrefix . $desc)) {
                            if (isset($el->description['sprintf'])) {
                                $desc = Member::loggedIn()->language()->addToStack(
                                    $this->formPrefix . $desc,
                                    false,
                                    ['sprintf' => $el->description['sprintf']]
                                );
                            } else {
                                $desc = Member::loggedIn()->language()->addToStack($this->formPrefix . $desc);
                            }
                        }
                        if ($desc === $el->description['key'] && Member::loggedIn()->language()->checkKeyExists(
                                $desc
                            )) {
                            if (isset($el->description['sprintf'])) {
                                $desc = Member::loggedIn()->language()->addToStack(
                                    $desc,
                                    false,
                                    ['sprintf' => $el->description['sprintf']]
                                );
                            } else {
                                $desc = Member::loggedIn()->language()->addToStack($desc);
                            }
                        }
                    }
                    if (isset($extra['warning'])) {
                        if (Member::loggedIn()->language()->checkKeyExists($extra['warning'])) {
                            $warning = Member::loggedIn()->language()->addToStack($extra['warning']);
                        } else {
                            $warning = $extra['warning'];
                        }
                    }
                    if (is_array($el->label) && isset($el->label['key'])) {
                        $label = $el->label['key'];
                        if (Member::loggedIn()->language()->checkKeyExists($this->formPrefix . $label)) {
                            if (isset($el->label['sprintf']) && is_array($el->label['sprintf'])) {
                                $label = Member::loggedIn()->language()->addToStack(
                                    $this->formPrefix . $label,
                                    ['sprintf' => $el->label['sprintf']]
                                );
                            } else {
                                $label = Member::loggedIn()->language()->addToStack($this->formPrefix . $label);
                            }
                        }
                        if ($label === $el->label['key'] && Member::loggedIn()->language()->checkKeyExists($label)) {
                            if (isset($el->label['sprintf']) && is_array($el->label['sprintf'])) {
                                $label = Member::loggedIn()->language()->addToStack(
                                    $label,
                                    ['sprintf' => $el->label['sprintf']]
                                );
                            } else {
                                $label = Member::loggedIn()->language()->addToStack($label);
                            }
                        }
                        $name = $label;
                    }
                    $this->form->addDummy($name, $default, $desc, $warning, $id);
                    break;
                case 'html':
                    $this->form->addHtml($default);
                    break;
                case 'matrix':
                    $this->form->addMatrix(
                        $el['name'],
                        $el['matrix'],
                        $el['after'],
                        $el['tab'] ?? $lastTab
                    );
                    break;
                case 'hidden':
                    $this->form->hiddenValues[$name] = $default;
                    break;
            }
        }

        if ($this->activeTab !== null) {
            $this->form->activeTab = $this->activeTab;
        }

//        if (empty($this->matrix) === false) {
//            foreach ($this->matrix as $matrix) {
//
//            }
//        }
        if ($this->dialogForm === true) {
            $this->customTemplateData =
                [
                    Theme::i()->getTemplate('forms', 'core'),
                    'popupTemplate'
                ];
        }

        if ($this->customTemplateData !== null) {
            $data = $this->customTemplateData;
            if ($this->includeItem) {
                $data = array_merge($this->customTemplateData, [$this->item]);
            }
            if ($this->builder === true) {
                $data = array_merge($this->customTemplateData, [$this->item], [$this->container]);
            }

            return $this->form->customTemplate(...$data);
        }
        if ($this->random === true) {
            $this->randomize();
        }
        return $this->form;
    }

    protected function randomize()
    {
        $elements = $this->form->elements;
        $count = count($elements);
        if ($count >= 2) {
            $this->shuffleAssoc($elements);
        } else {
            $noTabs = $elements[null];
            $this->shuffleAssoc($noTabs, false);
            $elements = [null => $noTabs];
        }
        $this->form->elements = $elements;
    }

    protected function shuffleAssoc(&$list, bool $includeValues = true)
    {
        $random = $list;
        $list = [];
        $keys = array_keys($random);
        if ($this->wizard === false) {
            shuffle($keys);
        }
        foreach ($keys as $key) {
            $values = $random[$key];
            if ($includeValues === true) {
                $this->shuffleAssoc($values, false);
            }
            $list[$key] = $values;
        }
    }

    public function randomOrder($rand)
    {
        $this->random = (bool)$rand;
        return $this;
    }

    /**
     * @param string $name
     * @param string $type
     * @param array $extra
     *
     * @return Element
     */
    public function addElement($name, $type = 'text', string $custom = '', ?array $placement = null): Element
    {
        $n = $name;
        if ($name instanceof FormAbstract) {
            $n = $name->name;
        }
        $element = new Element($name,$type,$custom);
        if(empty($placement) === false){
            $this->insertElement($placement['type'],$placement['element'], $n, $element);
        }
        else {
            $this->elementStore[$n] = $element;
        }
        return $this->elementStore[$n];
    }

    protected function insertElement($type,$index, $newKey, $element) {
        $store = $this->elementStore;
        if (!array_key_exists($index, $store)) {
            throw new \Exception("Index, {$index}, not found");
        }
        $tmpArray = array();
        foreach ($store as $key => $value) {
            if ($type === 'before' && $key === $index) {
                $tmpArray[$newKey] = $element;
            }
            $tmpArray[$key] = $value;
            if ($type === 'after' && $key === $index) {
                $tmpArray[$newKey] = $element;
            }
        }
        $this->elementStore = $tmpArray;
    }

    public function replaceElement($name, Element $element)
    {
        $n = $name;
        if ($name instanceof FormAbstract) {
            $n = $name->name;
        }
        $this->elementStore[$n] = $element;
    }

    public function addHelpers(FormAbstract $element)
    {
        $this->elementStore[$element->name] = $element;

        return $this;
    }

    public function header($header): Element
    {
        $this->elementStore[$header] = new Element($header, 'header');

        return $this->elementStore[$header];
    }

    public function removeHeader($header){
        unset($this->elementStore[$header]);
        return $this;
    }

    public function separator()
    {
        $name = 'separator_' . (count($this->elementStore) + 1);
        $this->elementStore[$name] = new Element($name, 'separator');

        return $this;
    }

    public function message(string $name, $css = '', array $sprintf = [], bool $first = false)
    {
        $key = $name . '_message';
        if ($first === true) {
            $toMerge = [
                $key => (new Element($name, 'message'))->extra(['css' => $css])
            ];
            $this->elementStore = $toMerge + $this->elementStore;
        } else {
            $this->elementStore[$key] = (new Element($name, 'message'))->extra(['css' => $css]);
        }
        return $this;
    }

    public function dummy(string $name, $value, ?string $label = null, ?string $desc = null, array $warning = [])
    {
        $key = $name . '_dummy';
        $this->elementStore[$key] = (new Element(
            $name, 'dummy'
        ))->value($value);
        if (empty($warning) !== true) {
            $this->element($key)->extra(['warning' => $warning]);
        }
        if ($desc !== null) {
            $this->element($key)->description($desc);
        }
        if ($label !== null) {
            $this->element($key)->label($label);
        }

        return $this;
    }

    /**
     * @param $name
     *
     * @return Element
     */
    public function element($name): Element
    {
        if (isset($this->elementStore[$name])) {
            return $this->elementStore[$name];
        }

        throw new InvalidArgumentException('element ' . $name . ' doesn\'t exist');
    }

    public function dbPrefix(bool $prefix = true)
    {
        $this->dbPrefix = $prefix;

        return $this;
    }

    public function noBitWise()
    {
        $this->doBitWise = false;

        return $this;
    }

    public function removePrefix(bool $strip = false)
    {
        $this->stripPrefix = $strip;

        return $this;
    }

    public function activeTab($tab)
    {
        $this->activeTab = $this->formPrefix . $tab . '_tab';

        return $this;
    }

    public function store(): array
    {
        return $this->elementStore;
    }

    public function tab($name, $icon=null, $blurbLang=null, $css=null )
    {
        $key = $name . '_tab';
        $tab = new Element($name, 'tab');
        $tab->options(['icon' => $icon, 'blurb' => $blurbLang, 'css' => $css]);
        $this->elementStore[$key] = $tab;
        return $this;
    }

    public function removeTab($name){
        $key = $name.'_tab';
        unset($this->elementStore[$key]);
        return $this;
    }

    public function clearPrevious($tab, $header)
    {
        $key = $tab . '_tab';
        unset($this->elementStore[$key]);
        if ($header) {
            unset($this->elementStore[$header]);
        }
        return $this;
    }

    public function html($html)
    {
        $name = sha1($html);
        $this->elementStore[$name] = (new Element($name, 'html'))->value($html);

        return $this;
    }

    public function createMatrix(string $name, Matrix $matrix, $after = null, $tab = null)
    {
        $n = $name;
        if ($name instanceof FormAbstract) {
            $n = $name->name;
        }
        $this->elementStore[$n] = [
            'name'   => $name,
            'matrix' => $matrix,
            'after'  => $after,
            'tab'    => $tab
        ];

        return $this;
    }

    public function hidden(string $name, $value, bool $suffix = true)
    {
        if ($suffix === true) {
            $key = $name . '_hidden';
        } else {
            $key = $name;
        }
        $this->elementStore[$key] = (new Element($name, 'hidden'))->value($value);

        return $this;
    }

    public function sideBar($content, $prefix = true)
    {
        $name = sha1($content);
        $this->elementStore[$name] = new Element($content, 'sidebar');
        if ($prefix === false) {
            $this->elementStore[$name]->skip();
        }

        return $this;
    }

    public function removeClass(string $class)
    {
        $classes = $this->form->class;
        $this->baseClass = str_replace($class, '', $this->baseClass);
        $this->form->class = str_replace($class, '', $classes);
        return $this;
    }

    public function saveAndReload(bool $reload = true)
    {
        $this->form->canSaveAndReload = $reload;
        if ($this->builder === true) {
            $this->addButton(
                'save_and_reload',
                'submit',
                null,
                'ipsButton ipsButton_primary',
                ['name' => 'save_and_reload', 'value' => 1]
            );
        }
        return $this;
    }

    public function addButton($lang, $type, $href = null, $class = '', $attributes = [])
    {
        $this->form->addButton($lang, $type, $href, $class, $attributes);
        return $this;
    }

    public function customTemplate($template)
    {
        $args = func_get_args();

        $this->customTemplateData = $args;

        return $this->build();
    }

    public function getLastUsedTab()
    {
        return $this->form->getLastUsedTab();
    }

    public function saveAsSettings($values = null)
    {
        if (is_array($values)) {
            foreach ($values as $key => $value) {
                if (is_array($value)) {
                    $values[$key] = json_encode($value);
                }
            }
        }
        if ($values === null) {
            $values = $this->values(true);
        }
        $this->form->saveAsSettings($values);
    }

    /**
     * @return bool|array
     */
    public function values($stringValues = false)
    {
        $name = "{$this->form->id}_submitted";
        $newValues = [];
        /* Did we submit the form? */
        if (isset(Request::i()->{$name}) && Login::compareHashes(
                (string)Session::i()->csrfKey,
                (string)Request::i()->csrfKey
            )) {
            if ($this->built === false) {
                $this->build();
            }
            if ($values = $this->form->values($stringValues)) {
                foreach ($values as $key => $value) {
                    $og = $key;
                    $key = $this->stripPrefix($key);
                    $dbPrefix = '';
                    if ($this->dbPrefix === true && $this->formPrefix && mb_strpos(
                            $og,
                            $this->formPrefix
                        ) !== false && is_object($this->object) && !($this->object instanceof Item) && property_exists(
                            $this->object,
                            'databasePrefix'
                        )) {
                        $object = $this->object;
                        $dbPrefix = $object::$databasePrefix;
                    }
                    $newValues[$dbPrefix . $key] = $value;
                }
            }

            if (empty($newValues) === false) {
                return $newValues;
            }
            $this->rebuild();
        }

        return false;
    }

    /**
     * @param $key
     *
     * @return string
     */
    public function stripPrefix($key): string
    {
        if ($this->formPrefix && $this->stripPrefix === true && mb_strpos($key, $this->formPrefix) !== false) {
            return mb_substr($key, mb_strlen($this->formPrefix));
        }

        return $key;
    }

    public function rebuild()
    {
        $this->built = false;
        $this->tabStore = [];
        $this->headerStore = [];
        $this->form->elements = [];
        return $this;
    }

    public function removeFromStore($name){
        $n = $name;
        if ($name instanceof FormAbstract) {
            $n = $name->name;
        }
        unset($this->elementStore[$n]);
        return $this;
    }
}

