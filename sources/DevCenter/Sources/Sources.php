<?php
/**
 * @brief      Elements Singleton
 * @copyright  -storm_copyright-
 * @package    IPS Social Suite
 * @subpackage dtdevplus
 * @since      -storm_since_version-
 * @version    -storm_version-
 */

namespace IPS\toolbox\DevCenter;

use Exception;
use IPS\Theme;
use Throwable;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use SplObserver;
use IPS\Http\Url;
use IPS\Application;
use IPS\Content\Tags;
use IPS\Node\Ratings;
use IPS\toolbox\Form;
use IPS\Content\Polls;
use IPS\Content\Views;
use IPS\Node\Colorize;
use IPS\Content\Hideable;
use IPS\Content\Lockable;
use IPS\Content\MetaData;
use IPS\Content\Pinnable;
use IPS\Content\Solvable;
use IPS\Node\Permissions;
use IPS\Content\Anonymous;
use IPS\Content\ItemTopic;
use IPS\Content\Reactable;
use IPS\Content\Shareable;
use IPS\Content\Embeddable;
use IPS\Content\Featurable;
use IPS\Content\Followable;
use IPS\Content\Reportable;
use IPS\Content\Searchable;
use IPS\Content\Statistics;
use IPS\Content\EditHistory;
use IPS\Content\ReadMarkers;
use InvalidArgumentException;
use IPS\Content\Recognizable;
use UnexpectedValueException;
use IPS\Content\ClubContainer;
use IPS\toolbox\ReservedWords;
use IPS\Content\FuturePublishing;
use IPS\toolbox\DevCenter\Sources\Generator\Elements;
use IPS\toolbox\DevCenter\Sources\SourcesFormAbstract;
use IPS\toolbox\DevCenter\Sources\SourceBuilderException;

use IPS\toolbox\DevCenter\Sources\Generator\GeneratorAbstract;

use function _p;
use function count;
use function header;
use function defined;
use function in_array;
use function is_array;
use function array_keys;
use function mb_ucfirst;
use function array_search;
use function class_exists;
use function trait_exists;
use function mb_strtolower;
use function property_exists;
use function interface_exists;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * Sources Class
 *
 * @mixin Sources
 */
class _Sources
{
    /**
     * @var Form
     */
    public $form;

    public $type;

    /**
     * @var Application
     */
    protected $application;

    /**
     * @var array
     */
    protected $elements = [];

    /**
     * @var string
     */
    protected $types;

    protected $findClassWithApp = [];

    protected $findClass = [];

    protected $findNameSpace = [];

    protected $findInterfaces = [];

    protected $findTraits = [];

    /**
     * _Elements constructor.
     *
     * @param Application $application
     */
    public function __construct(Application $application = null)
    {
        $this->application = $application;
        $url = 'app=toolbox&module=devcenter&controller=sources&appKey=' . $this->application->directory;
        $base = [
            'source' => (string) $url . '&do=findClass',
            'minimized' => false,
            'commaTrigger' => false,
            'unique' => true,
            'minAjaxLength' => 2,
            'disallowedCharacters' => [],
            'maxItems' => 1,
        ];
        $this->findClass = $base;
        $base['source'] = $url . '&do=findClassWithApp';
        $this->findClassWithApp = $base;
        $base['source'] = $url . '&do=findNameSpace';
        $this->findNameSpace = $base;
        $base['source'] = $url . '&do=findClass&type=interface';
        $this->findInterfaces = $base;
        $base['source'] = $url . '&do=findClass&type=trait';
        $this->findTraits = $base;
        $this->form = Form::create();
    }

    /**
     * @throws UnexpectedValueException
     */
    public static function menu()
    {
        if (Request::i()->controller === 'sources' || Request::i()->controller === 'devFolder') {
            Output::i()->sidebar['actions']['devcenter'] = [
                'icon'  => null,
                'title' => 'dtdevplus_devcenter',
                'link'  => (string) Url::internal(
                    'app=core&module=applications&controller=developer&appKey=' . Request::i()->appKey
                )->csrf(),
            ];
        }
        Output::i()->sidebar['actions']['sources'] = [
            'icon' => 'arrow-down',
            'title' => 'dtdevplus_sources',
            'link' => (string) Url::internal(
                'app=toolbox&module=devcenter&controller=sources&appKey=' . Request::i()->appKey
            ),
            'id' => 'adminMenu_button',
            'data' => [
                'ipsDialog' => 1,
                'ipsDialog-destructOnClose' => 1,
                'ipsDialog-remoteSubmit' => 1
            ],
        ];

        Output::i()->sidebar['actions']['dev'] = [
            'icon' => 'code',
            'title' => 'dtdevplus_dev',
            'link' => (string) Url::internal(
                'app=toolbox&module=devcenter&controller=dev&appKey=' . Request::i()->appKey
            ),
            'id' => 'adminMenuDev_button',
            'data' => [
//                'ipsMenu' => 1
                'ipsDialog' => 1,
                'ipsDialog-destructOnClose' => 1,
                'ipsDialog-remoteSubmit' => 1
            ],
        ];
        Output::i()->sidebar['actions']['Adminer'] = [
            'icon'  => 'database',
            'title' => 'dtdevplus_open_in_adminer',
            'link'  => (string) Url::internal('app=toolbox&module=settings&controller=adminer&dbApp='.Request::i()->appKey),
            'id'    => 'adminMenuAdminer_button',
        ];
        Output::i()->sidebar['mobilenav'] = static::subMenus();
    }

    public static function getSubMenus()
    {
        return [
            'sources' => [
                'standard',
                'cinterface',
                'ctraits',
                'singleton',
                'ar',
                'api',
                'node',
                'item',
                'comment',
                'review',
                'oauthApi',
                'debug',
                'memory',
                'member',
                'form',
                'orm',
                'settings',
                'application'
            ],
            'check' => [
                'Debug',
                'Memory',
                'Member',
                'Form',
                'Orm',
                'Settings',
            ],
            'ignored' => [
                'debug',
                'memory',
                'member',
                'form',
                'orm',
                'settings'
            ],
            'dev' => [
                'template',
                'widget',
                'module',
                'controller',
                'jstemplate',
                'jsmixin',
                'debugger'
            ]
        ];
    }

    public static function processedSubMenus()
    {
        $menus = static::getSubMenus();
        $subs = $menus['sources'];
        $ns = '\\IPS\\' . Request::i()->appKey;
        foreach ($menus['check'] as $ignored) {
            if ($ignored === 'Application') {
                $ignored = 'ApplicationOG';
            }
            $og = $ns;
            if ($ignored === 'Orm') {
                $ns .= '\\Traits';
            }
            if (in_array($ignored, ['Debug', 'Memory'], true)) {
                $ns .= '\\Profiler';
            }
            $cs = $ns . '\\' . $ignored;
            try {
                if (class_exists($cs) || trait_exists($cs) || interface_exists($cs)) {
                    $key = array_search(mb_strtolower($ignored), $subs, true);
                    if ($key !== false) {
                        unset($subs[$key]);
                    }
                }
            } catch (Throwable|Exception $e) {
            }
            $ns = $og;
        }
        $menus['sources'] = $subs;

        return $menus;
    }

    public static function subMenus()
    {
        $menus = static::processedSubMenus();

        return Theme::i()->getTemplate('dtdpmenu', 'toolbox', 'admin')->menu(
            $menus['sources'],
            $menus['dev'],
            Request::i()->appKey,
            'sources',
            $menus['ignored']
        );
    }

    /**
     * @param array $config
     * @param string $type
     */
    public function buildForm(array $config, string $type)
    {
        $this->type = $type;
        $this->form
            ->dialogForm()
            ->setPrefix('dtdevplus_class_')
            ->addExtraPrefix('_r' . $this->type . 'r_')
            ->setId('dtdevplus_class__r' . $this->type . 'r_')
            ->submitLang('Create Source');
            foreach ($config as $func) {
                if($func instanceof Form\Element){
                    $this->form->addToElementStore($func);
                }
                else {
                    $method = 'el' . $func;
                    $this->{$method}();
                }
            }
    }

    /**
     * create file
     */
    public function create()
    {
        if ($values = $this->form->values()) {
            return $this->generate($values);
        }
    }

    public function generate(array $values = [])
    {
        /* @var Application $app */
        foreach (Application::allExtensions('toolbox', 'SourcesFormAbstract') as $app) {
            /* @var SourcesFormAbstract $extension */
            foreach ($app->extensions('toolbox', 'SourcesFormAbstract') as $extension) {
                $extension->formProcess($values);
            }
        }
        /* @var GeneratorAbstract $class */
        $class = 'IPS\\toolbox\DevCenter\\Sources\\Generator\\';
        $og = $class;
        $type = $this->type;
        $values['type'] = mb_ucfirst($type);
        try {
            switch ($type) {
                case 'Memory':
                case 'Debug':
                    $class .= 'Profiler';
                    $values['className'] = mb_ucfirst($type);
                    $values['namespace'] = 'Profiler';
                    break;
                case 'Member':
                    $class .= 'Member';
                    $values['className'] = 'Member';
                    $values['namespace'] = '';
                    $values['prefix'] = $this->application->directory . '_member';
                    $values['scaffolding_create'] = true;
                    $values['scaffolding_type'] = ['db'];
                    break;
                case 'Form':
                    $class .= 'Form';
                    $values['className'] = 'Form';
                    $values['namespace'] = '';
                    $values['extends'] = \IPS\Helpers\Form::class;
                    break;
                case 'Orm':
                    $class .= 'Orm';
                    $values['type'] = 'Traits';
                    $values['className'] = 'Orm';
                    $values['namespace'] = 'Traits';
                    break;
                case 'Settings':
                    $class .= 'Settings';
                    $values['className'] = 'Settings';
                    $values['namespace'] = '';
                    break;
                default:
                    $class .= mb_ucfirst($type);
                    break;
            }

            $class = new $class($values, $this->application);
            $class->process();

            $msg = Member::loggedIn()->language()->addToStack(
                'dtdevplus_class_created',
                false,
                [
                    'sprintf' => [
                        $type,
                        $class->classname,
                    ],
                ]
            );
        } catch (SourceBuilderException $e) {
            $msg = $e->getMessage();
        }

        return $msg;
    }

    /**
     * checks to see if the class doesn't exist and the classname is good
     *
     *
     * @param $data
     *
     * @throws InvalidArgumentException
     */
    public function classCheck($data)
    {
        $this->noBlankCheck($data);
        $ns = 'dtdevplus_class__r' . $this->type . 'r_namespace';
        $ns = mb_ucfirst(Request::i()->{$ns});
        $class = mb_ucfirst($data);
        $class = $ns ? '\\IPS\\' . $this->application->directory . '\\' . $ns . '\\' . $class : '\\IPS\\' . $this->application->directory . '\\' . $class;

        if ($data !== 'Form' && class_exists($class)) {
            throw new InvalidArgumentException('dtdevplus_class_exists');
        }

        if (ReservedWords::check($data)) {
            throw new InvalidArgumentException('dtdevplus_class_reserved');
        }
    }

    /**
     * checks to see if the trait doesn't exist and the trait name is good!
     *
     * @param $data
     *
     * @throws InvalidArgumentException
     *
     */
    public function traitClassCheck($data)
    {
        $this->noBlankCheck($data);
        $ns = 'dtdevplus_class__r' . $this->type . 'r_namespace';
        $ns = mb_ucfirst(Request::i()->{$ns});
        $class = mb_ucfirst($data);
        if ($ns) {
            $class = '\\IPS\\' . $this->application->directory . '\\' . $ns . '\\' . $class;
        } else {
            $class = '\\IPS\\' . $this->application->directory . '\\' . $class;
        }

        if (trait_exists($class)) {
            throw new InvalidArgumentException('dtdevplus_class_trait_exists');
        }

        if (ReservedWords::check($data)) {
            throw new InvalidArgumentException('dtdevplus_class_reserved');
        }
    }

    /**
     * checks to see if the interface doesn't exist and the name is good!
     *
     * @param $data
     *
     * @throws InvalidArgumentException
     *
     */
    public function interfaceClassCheck($data)
    {
        $this->noBlankCheck($data);
        $ns = 'dtdevplus_class__r' . $this->type . 'r_namespace';
        $ns = mb_ucfirst(Request::i()->{$ns});
        $class = mb_ucfirst($data);
        if ($ns) {
            $class = "\\IPS\\" . $this->application->directory . "\\" . $ns . "\\" . $class;
        } else {
            $class = "\\IPS\\" . $this->application->directory . "\\" . $class;
        }

        if (interface_exists($class)) {
            throw new InvalidArgumentException('dtdevplus_class_interface_exists');
        }

        if (ReservedWords::check($data)) {
            throw new InvalidArgumentException('dtdevplus_class_reserved');
        }
    }

    /**
     * checks to see if the Class/Trait/Interface name isn't blank!
     *
     * @param $data
     *
     * @throws InvalidArgumentException
     *
     */
    public function noBlankCheck($data)
    {
        if (!$data) {
            throw new InvalidArgumentException('dtdevplus_class_no_blank');
        }
    }

    /**
     * checks the parent class exist if one is provided
     *
     * @param $data
     *
     * @throws InvalidArgumentException
     *
     */
    public function extendsCheck($data)
    {
        if ($data && (!class_exists($data, true) && !class_exists('\\IPS\\' . $data))) {
            throw new InvalidArgumentException('dtdevplus_class_extended_class_no_exist');
        }
    }

    /**
     * Checks to make sure the interface files exist
     *
     * @param $data
     *
     * @throws InvalidArgumentException
     *
     */
    public function implementsCheck($data)
    {
        if (is_array($data) && count($data)) {
            foreach ($data as $implement) {
                if (!interface_exists($implement)) {
                    $lang = Member::loggedIn()->language()->addToStack(
                        'dtdevplus_class_implemented_no_interface',
                        false,
                        ['sprintf' => $implement]
                    );
                    Member::loggedIn()->language()->parseOutputForDisplay($lang);
                    throw new InvalidArgumentException($lang);
                }
            }
        }
    }

    /**
     * checks to make sure the traits being used exists
     *
     * @param $data
     *
     * @throws InvalidArgumentException
     *
     */
    public function traitsCheck($data)
    {
        if (is_array($data) && count($data)) {
            foreach ($data as $trait) {
                if (!trait_exists($trait)) {
                    $lang = Member::loggedIn()->language()->addToStack(
                        'dtdevplus_class_no_trait',
                        false,
                        ['sprintf' => [$trait]]
                    );
                    Member::loggedIn()->language()->parseOutputForDisplay($lang);
                    throw new InvalidArgumentException($lang);
                }
            }
        }
    }

    /**
     * checks to make sure the node exist for the content item class.
     *
     * @param $data
     *
     * @throws InvalidArgumentException
     *
     */
    public function itemNodeCheck($data)
    {
        if ($data) {
            $class = "IPS\\{$this->application->directory}\\{$data}";
            if (!class_exists($class)) {
                throw new InvalidArgumentException('dtdevplus_class_node_item_missing');
            }

            if (ReservedWords::check($data)) {
                throw new InvalidArgumentException('dtdevplus_class_reserved');
            }
        }
    }

    /**
     * namespace element
     */
    protected function elNamespace()
    {
        $tabs = [
            'node',
            'item',
            'comment',
            'review',
        ];

        if (in_array($this->type, $tabs, true)) {
            $this->form->addTab('general');
        }

        $options = [
            'placeholder' => 'Namespace',
            'autocomplete' => $this->findNameSpace,

        ];
        $this
            ->form
            ->addElement('namespace')
            ->options($options)
            ->prefix("IPS\\{$this->application->directory}\\");
    }

    /**
     * classname element
     */
    protected function elClassName()
    {
        $prefix = null;
        if ($this->type === 'interfacing') {
            $placeholder = 'Interface Name';
            $name = 'interfaceName';
            $validate = [$this, 'interfaceClassCheck'];
        } elseif ($this->type === 'traits') {
            $placeholder = 'Trait Name';
            $name = 'traitName';
            $validate = [$this, 'traitClassCheck'];
        } else {
            $placeholder = 'Class Name';
            $name = 'className';
            $validate = [$this, 'classCheck'];
            $prefix = '_';
        }

        $this
            ->form
            ->addElement($name)
            ->options(['placeHolder' => $placeholder])
            ->prefix($prefix)
            ->validation($validate)
            ->appearRequired();
    }

    /**
     * abstract element
     */
    protected function elAbstract()
    {
        $this->form->addElement('abstract', 'yn');
    }

    /**
     * extends element
     */
    protected function elExtends()
    {
        $options = [
            'autocomplete' => $this->findClass,
            'minimized' => null
        ];
        $this->form->addElement('extendsYN', 'yn')->toggles(['extends']);
        $this->form->addElement('extends')->options($options)->validation([$this, 'extendsCheck'])->prefix('IPS\\');
    }

    /**
     * imports element
     *
     * @deprecated no longer gonna support non-imports
     */
    protected function elImports()
    {
    }

    /**
     * database element
     */
    protected function elDatabase()
    {
        $this->form->addElement('database')->prefix($this->application->directory . '_');
    }

    /**
     * prefix element
     */
    protected function elPrefix()
    {
        $this->form->addElement('prefix')->suffix('_');
    }

    /**
     * scaffolding element
     */
    protected function elScaffolding()
    {
        $this->form->addElement('scaffolding_create', 'yn')->empty(true)->toggles(['scaffolding_type']);

        $sc['db'] = 'Database';

        if (!in_array($this->type, ['activerecord', 'review', 'comment'], true)) {
            $sc['modules'] = 'Module';
        }

        $this->form->addElement('scaffolding_type', 'checkboxset')->value(array_keys($sc))->options(['options' => $sc]);
    }

    /**
     * subnode element
     */
    protected function elSubNode()
    {
        $this->form->addElement('subnode', 'yn')->toggles(['subnode_class'])->toggles(['parentnode_class'], true);
        $this->form->addElement('parentnode_class')->prefix('\\IPS\\' . $this->application->directory . '\\')->options(
            [
                'autocomplete' => $this->findClassWithApp,
                'minimized' => null
            ]
        );
        $this->form->addElement('subnode_class')->prefix('\\IPS\\' . $this->application->directory . '\\')->options(
            [
                'autocomplete' => $this->findClassWithApp,
                'minimized' => null
            ]
        );
    }

    /**
     * Item Class element
     */
    protected function elItemClass()
    {
        $this->form->addElement('item_class')->prefix('IPS\\' . $this->application->directory)->options(
            [
                'autocomplete' => $this->findClassWithApp,
                'minimized' => null
            ]
        );
    }

    /**
     * interfaces tab for nodes
     */
    protected function elNodeInterfaces()
    {
        $interfacesNode = [
            Permissions::class => Permissions::class,
            Ratings::class     => Ratings::class,
        ];

        $this->form->addTab('interfaces');
        $this->form->addElement('ips_implements', 'checkboxset')
                   ->label('interface_implements_node')
                   ->options(['options' => $interfacesNode]);
        $this->elInterfaces();
    }

    /**
     * interface  element
     */
    protected function elInterfaces()
    {
        $autoComplete = $this->findInterfaces;
        unset($autoComplete['maxItems']);
        $this->form->addElement('implements')
            ->options(
                [
                    'autocomplete' => $autoComplete
                ]
            )
            ->validation([$this, 'implementsCheck']);
    }

    protected function arTraits($traits)
    {
        $trait = '\\IPS\\' . $this->application->directory . '\\Traits\Orm';
        if (trait_exists($trait)) {
            return array_merge([$trait => $trait], $traits);
        }
        return $traits;
    }

    /**
     * traits tab for nodes
     */
    protected function elNodeTraits()
    {
        $traitsNode = [
            ClubContainer::class => ClubContainer::class,
            Colorize::class      => Colorize::class,
        ];

        $traitsNode = $this->arTraits($traitsNode);
        $this->form->addTab('traits');
        $this->form->addElement('ips_traits', 'checkboxset')->label('ips_traits_node')->options(['options' => $traitsNode]);

        $this->elTraits();
    }

    /**
     * traits element
     */
    protected function elTraits()
    {
        $trait = '\\IPS\\' . $this->application->directory . '\\Traits\Orm';

        if ($this->type === 'activerecord' && trait_exists($trait)) {
            $traits = [];
            $traits = $this->arTraits($traits);
            $this->form->addElement('ips_traits', 'checkboxset')
                ->label('ips_traits_item')
                ->value([])
                ->options(['options' => $traits]);
        }
        $autoComplete = $this->findTraits;
        unset($autoComplete['maxItems']);
        $this->form->addElement('traits')->options(['autocomplete' => $autoComplete])->validation(
            [$this, 'traitsCheck']
        );
    }

    /**
     * traits for items/comments/reviews
     */
    protected function elItemTraits()
    {
        $traitsItems = [
            ItemTopic::class  => ItemTopic::class,
            Reactable::class  => Reactable::class,
            Recognizable::class => Recognizable::class,
            Reportable::class => Reportable::class,
            Solvable::class   => Solvable::class,
            Statistics::class => Statistics::class
        ];
        $traitsItems = $this->arTraits($traitsItems);
        $this->form->addTab('traits');
        $this->form->addElement('ips_traits', 'checkboxset')
                   ->label('ips_traits_item')
                   ->value([])
                   ->options(['options' => $traitsItems]);

        $this->elTraits();
    }

    /**
     * interfaces tab for items
     */
    protected function elItemInterfaces()
    {
        $interfacesItem = [
            Anonymous::class =>Anonymous::class,
            EditHistory::class              => EditHistory::class,
            Embeddable::class               => Embeddable::class,
            Featurable::class               => Featurable::class,
            Followable::class               => Followable::class,
            FuturePublishing::class         => FuturePublishing::class,
            Hideable::class                 => Hideable::class,
            Lockable::class                 => Lockable::class,
            MetaData::class                 => MetaData::class,
            \IPS\Content\Permissions::class => \IPS\Content\Permissions::class,
            Pinnable::class                 => Pinnable::class,
            Polls::class                    => Polls::class,
            SplObserver::class              => SplObserver::class,
            \IPS\Content\Ratings::class     => \IPS\Content\Ratings::class,
            ReadMarkers::class              => ReadMarkers::class,
            Searchable::class               => Searchable::class,
            Shareable::class                => Shareable::class,
            Tags::class                     => Tags::class,
            Views::class                    => Views::class,
        ];

        $this->form->addTab('interfaces');
        $this->form->addElement('ips_implements', 'checkboxset')
                   ->label('interface_implements_item')
                   ->value([])
                   ->options(['options' => $interfacesItem]);

        $this->elInterfaces();
    }

    /**
     * Item's node class
     */
    protected function elItemNodeClass()
    {
        $this->form->addElement('item_node_class')->prefix('IPS\\' . $this->application->directory . '\\')->options(
            [
                'autocomplete' => $this->findClassWithApp,
                'minimized' => null
            ]
        );
    }

    /**
     * item's comment class
     */
    protected function elItemCommentClass()
    {
        $this->form->addElement('comment_class')->prefix('IPS\\' . $this->application->directory . '\\')->options(
            [
                'autocomplete' => $this->findClassWithApp,
                'minimized' => null
            ]
        );
    }

    /**
     * item's review class
     */
    protected function elItemReviewClass()
    {
        $this->form->addElement('review_class')->prefix('IPS\\' . $this->application->directory . '\\')->options(
            [
                'autocomplete' => $this->findClassWithApp,
                'minimized' => null
            ]
        );
    }

    /**
     * interfaces tab for comments/reviews
     */
    protected function elCommentInterfaces()
    {
        $interfacesComment = [
            Anonymous::class =>Anonymous::class,
            Hideable::class    => Hideable::class,
            Embeddable::class  => Embeddable::class,
            Searchable::class  => Searchable::class,
            Lockable::class    => Lockable::class,
            EditHistory::class => EditHistory::class,
        ];
        $this->form->addTab('interfaces');
        $this->form->addElement('interface_implements_comment', 'checkboxset')->empty(array_keys($interfacesComment))->options(
            ['options' => $interfacesComment]
        );

        $this->elInterfaces();
    }

    /**
     * Comment/review item's class
     */
    protected function elContentItemClass()
    {
        $this->form->addElement('content_item_class')->prefix('IPS\\' . $this->application->directory . '\\')->options(
            [
                'autocomplete' => $this->findClassWithApp,
                'minimized' => null
            ]
        );
    }

    protected function elApiType()
    {
        $this->form->addElement('apiType', 'select')->options(
            [
                'options' => [
                    's' => 'Standard',
                    'i' => 'Content/Item',
                    'c' => 'Comment',
                    'n' => 'Node',
                ],
            ]
        );
    }
}
