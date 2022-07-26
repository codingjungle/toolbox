//<?php namespace toolbox_IPS_Plugin_Hook_ab9712a0d65901062b22f5262a724bd72;

use DomainException;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Table\Db;
use IPS\Http\Url;
use IPS\Plugin\Hook;
use IPS\Request;

use IPS\toolbox\Application;

use ReflectionException;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

class toolbox_hook_Hooks extends _HOOK_CLASS_
{

    /**
     * @param Url $url
     * @param $appOrPluginId
     * @param $hookDir
     * @return Form|Db
     * @throws ReflectionException
     */
    public static function devTable($url, $appOrPluginId, $hookDir)
    {
        Application::loadAutoLoader();
        $dtProxyFolder = \IPS\Application::getRootPath() . '/dtProxy/namespace.json';

        $parent = parent::devTable($url, $appOrPluginId, $hookDir);
        /** @var Form $parent */
        if ($parent instanceof Form && file_exists($dtProxyFolder)) {
            $elements = $parent->elements;
            $options = [
                'placeholder'  => 'Namespace',
                'autocomplete' => [
                    'source'               => 'app=toolbox&module=devcenter&controller=sources&do=findClass&appKey=' . Request::i()->appKey,
                    'minimized'            => false,
                    'commaTrigger'         => false,
                    'unique'               => true,
                    'minAjaxLength'        => 3,
                    'disallowedCharacters' => [],
                    'maxItems'             => 1,
                ],
            ];

            unset($elements['plugin_hook_class']);
            $parent->elements = $elements;

            $parent->add(
                new Text(
                    'plugin_hook_class', null, true, $options, static function ($val) {
                    if ($val && !class_exists('IPS\\' . $val)) {
                        throw new DomainException('plugin_hook_class_err');
                    }
                }, 'IPS\\', null, 'plugin_hook_class'
                )
            );
        }

        return $parent;
    }


}
