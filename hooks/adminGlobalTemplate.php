//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Lang;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use IPS\toolbox\Profiler;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    exit;
}

class toolbox_hook_adminGlobalTemplate extends _HOOK_CLASS_
{

    /* !Hook Data - DO NOT REMOVE */
    public static function hookData()
    {
        if (\is_callable('parent::hookData')) {
            return array_merge_recursive(
                [
                    'globalTemplate' => [
                        0 => [
                            'selector' => '#ipsLayout_header',
                            'type'     => 'add_inside_start',
                            'content'  => '{{if $menu = \IPS\toolbox\Menu::i()->build()}}
	{$menu|raw}
{{endif}}',
                        ],
                        1 => [
                            'selector' => 'html > body',
                            'type'     => 'add_inside_end',
                            'content'  => '<!--ipsQueryLog-->',
                        ],
                    ],
                ],
                parent::hookData()
            );
        }
        return [];
    }

    /* End Hook Data */
    public function globalTemplate($title, $html, $location = [])
    {
        Member::loggedIn()->language()->words['support'] = '';
        Member::loggedIn()->language()->words['site'] = '';

        Output::i()->cssFiles = array_merge(
                Output::i()->cssFiles,
                Theme::i()->css('devbar.css', 'toolbox', 'admin')
            );
        $return = '';
        if ( \is_callable('parent::globalTemplate') )
        {
            $return = \call_user_func_array( 'parent::' . __FUNCTION__, \func_get_args() );
        }
        $hide = defined('DT_HIDE_MYAPPS') ? DT_HIDE_MYAPPS : false;
        if (
            !$hide &&
            !\IPS\QUERY_LOG &&
            !\IPS\Request::i()->isAjax()
        ) {
            try {
                $myapps = Profiler::i()->justMyApps();
                $return = str_replace('</body>', $myapps.'</body>', $return);
            } catch (Exception $e) {
                \IPS\toolbox\Profiler\Debug::log($e);
            }
        }

        return $return;
    }

    public function tabs(
        $tabNames,
        $activeId,
        $defaultContent,
        $url,
        $tabParam = 'tab',
        $tabClasses = '',
        $panelClasses = ''
    ) {
            if (Request::i()->app === 'core' && Request::i()->module === 'applications' && Request::i(
                )->controller === 'developer' && !Request::i()->do) {
             $tabNames['SchemaImports'] = 'dtdevplus_schema_imports';
            }
        if ( \is_callable('parent::tabs') )
        {
            return \call_user_func_array( 'parent::' . __FUNCTION__, [$tabNames, $activeId, $defaultContent, $url, $tabParam, $tabClasses, $panelClasses] );
        }

    }
}
