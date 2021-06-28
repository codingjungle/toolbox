//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Data\Store;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use IPS\toolbox\Application;

use const IPS\CACHING_LOG;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    exit;
}

class toolbox_hook_coreGlobalGlobalTheme extends _HOOK_CLASS_
{
    /* !Hook Data - DO NOT REMOVE */
    public static function hookData()
    {
        if (\is_callable('parent::hookData')) {
            return parent::hookData();
        }
        return [];
    }

    /* End Hook Data */

    public function includeCSS()
    {
            $css = Output::i()->cssFiles;

            $caching = Theme::i()->css('styles/caching_log.css', 'core', 'front');
            $cachingCss = array_pop($caching);
            if (CACHING_LOG && $key = array_search($cachingCss, $css)) {
                unset(Output::i()->cssFiles[$key]);
            }
            if (\IPS\QUERY_LOG && !Request::i()->isAjax()) {
                Output::i()->cssFiles = array_merge(
                    Output::i()->cssFiles,
                    Theme::i()->css('profiler.css', 'toolbox', 'front')
                );
                $query = Theme::i()->css('styles/query_log.css', 'core', 'front');
                $queryCss = array_pop($query);
                if ($key = array_search($queryCss, $css)) {
                    unset(Output::i()->cssFiles[$key]);
                }

                if (Settings::i()->dtprofiler_enabled_css) {
                    Store::i()->dtprofiler_css = Output::i()->cssFiles;
                }
            }

            return parent::includeCSS();

    }

    public function includeJS()
    {
            if (\IPS\QUERY_LOG && !Request::i()->isAjax()) {
                //Output::i()->jsFiles = array_merge(Output::i()->jsFiles, Output::i()->js('front_profiler.js', 'toolbox', 'front' ) );
                Application::addJs(['front_profiler']);
                Application::addJs(['global_proxy'], 'global');
                if (Settings::i()->dtprofiler_enabled_js) {
                    Store::i()->dtprofiler_js = Output::i()->jsFiles;
                }
                if (Settings::i()->dtprofiler_enabled_jsvars) {
                    Store::i()->dtprofiler_js_vars = Output::i()->jsVars;
                }
            }

            return parent::includeJS();

    }
}
