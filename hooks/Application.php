//<?php namespace toolbox_IPS_Application_a9c79968882bc47948bd3964ea259cdf0;

use Exception;
use IPS\Application;
use IPS\Settings;
use IPS\toolbox\DevCenter\Headerdoc;
use IPS\toolbox\DevFolder\Applications;
use IPS\toolbox\Proxy\Generator\Proxy;
use IPS\toolbox\Proxy\Proxyclass;

use function _p;
use function date;
use function defined;
use function explode;
use function array_keys;
use function file_exists;
use function array_merge;
use function array_combine;
use function array_unshift;
use function file_get_contents;
use function is_dir;
use function mb_ucfirst;
use function mkdir;
use function str_replace;

use const DT_MY_APPS;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}


/**
 * Class toolbox_hook_Application
 * @mixin Application
 */
class toolbox_hook_Application extends _HOOK_CLASS_
{
    public $skip = false;

    /**
     * @inheritdoc
     */
    public function assignNewVersion($long, $human)
    {
        parent::assignNewVersion($long, $human);
        if (static::appIsEnabled('toolbox')) {
            $this->version = $human;
            Headerdoc::i()->process($this);
        }
    }

    /**
     * @inheritdoc
     */
    public function build()
    {
        if (static::appIsEnabled('toolbox')) {
            Headerdoc::i()->addIndexHtml($this);
        }
        parent::build();
    }

    public function buildHooks()
    {
        parent::buildHooks();
        Proxyclass::i()->buildHooks();
    }

    /**
     * @inheritdoc
     */
    public function installOther()
    {
        if (\IPS\IN_DEV && $this->marketplace_id === null) {
            $dir = \IPS\Application::getRootPath() . '/applications/' . $this->directory . '/dev/';
            if (!file_exists($dir)) {
                try {
                    $app = new Applications($this);
                    $app->addToStack = true;
                    $app->email();
                    $app->javascript();
                    $app->language();
                    $app->templates();
                } catch (Exception $e) {
                }
            }
        }

        parent::installOther();
    }

    public static function writeJson($file, $data)
    {
        parent::writeJson($file, $data);
        if (mb_strpos($file, 'settings.json') !== false) {
            Settings::i()->clearCache();
            Proxy::i()->generateSettings();
        }
    }

    public function form(&$form)
    {
        parent::form($form);
        $form = \IPS\toolbox\Form::create($form);
        if(!$this->id) {
            $form->addElement('devCenterPlusCreateFrontNavigation', 'yn');
        }
    }

    protected $isNew = false;

    protected $doFrontNav = false;

    public function formatFormValues($values)
    {
        $this->isNew = $this->id ? false : true;
        if($this->isNew && isset($values['devCenterPlusCreateFrontNavigation'])){
            $this->doFrontNav = (bool) $values['devCenterPlusCreateFrontNavigation'];
        }
        unset($values['devCenterPlusCreateFrontNavigation']);
        return parent::formatFormValues($values);

    }

    public function postSaveForm($values)
    {
        $parent = parent::postSaveForm($values);
        if($this->isNew === true && $this->doFrontNav === true){
            $extName = mb_ucfirst($values['app_directory']);
            $contents = str_replace(
                array(
                    "{subpackage}",
                    '{date}',
                    '{app}',
                    '{class}',
                ),
                array(
                    $values['app_directory'],
                    date( 'd M Y' ),
                    $values['app_directory'],
                    $extName
                ),
                file_get_contents( \IPS\ROOT_PATH . '/applications/core/data/defaults/extensions/FrontNavigation.txt' )
            );

            $dir = \IPS\ROOT_PATH . "/applications/{$values['app_directory']}/extensions/core/FrontNavigation";
            if ( !is_dir( $dir ) )
            {
                mkdir( $dir, \IPS\IPS_FOLDER_PERMISSION, TRUE );
            }
            $application = Application::load($values['app_directory']);

            @\file_put_contents( \IPS\ROOT_PATH . "/applications/{$values['app_directory']}/extensions/core/FrontNavigation/{$extName}.php", $contents );

            try
            {
                \IPS\Application::writeJson(
                        \IPS\ROOT_PATH . "/applications/{$values['app_directory']}/data/extensions.json",
                        $application->buildExtensionsJson()
                );
            }
            catch ( \RuntimeException $e )
            {
                \IPS\Output::i()->error( 'dev_could_not_write_data', '1TB103/4', 403, '' );
            }

            @\file_put_contents( \IPS\ROOT_PATH . '/applications/' . $values['app_directory'] . '/Application.php', str_replace(
                array(
                    '{app}',
                    '{website}',
                    '{author}',
                    '{year}',
                    '{subpackage}',
                    '{date}',
                    '{ext}'
                ),
                array(
                    $values['app_directory'],
                    $values['app_website'],
                    $values['app_author'],
                    date('Y'),
                    $values['app_title'],
                    date( 'd M Y' ),
                    $extName
                ),
                file_get_contents( \IPS\ROOT_PATH . "/applications/toolbox/data/defaults/Application.txt" )
            ) );
        }

        return $parent;
    }

    public function getButtons($url, $subnode = false)
    {
        $thirdParty = \IPS\toolbox\Application::$thirdParty;
        $apps = [];
        $buttons = parent::getButtons($url, $subnode);
        if(defined('DT_MY_APPS')) {
            $apps = \is_array(DT_MY_APPS) ? DT_MY_APPS : explode(',', DT_MY_APPS);
            $apps = array_combine($apps, $apps);
        }
        if(isset($thirdParty[$this->directory])){
            $buttons['addToMyApps']  = [
                'icon' => 'plus',
                'title' => 'addToMyApps',
                'link' => $url->setQueryString(['do' => 'addToMyApps', 'appKey' => $this->directory]),
                'data' => [
                    'confirm' => \IPS\Member::loggedIn()->language()->addToStack('addToMyApps'),
                ],
            ];
        }
        elseif(isset($apps[$this->directory])){
            $buttons['removeFromMyApps'] = [
                'icon' => 'minus',
                'title' => 'removeFromMyApps',
                'link' => $url->setQueryString(['do' => 'removeFromMyApps', 'appKey' => $this->directory]),
                'data' => [
                    'confirm' => \IPS\Member::loggedIn()->language()->addToStack('removeFromMyApps'),
                ],
            ];
        }

        return $buttons;
    }
}
