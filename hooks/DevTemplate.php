//<?php namespace toolbox_IPS_Theme_Dev_Template_ac409d81bb8f8d5165119ca65e20be252;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Data\Store;
use IPS\Request;
use IPS\Settings;
use IPS\n2a\Profiler\Debug;
use IPS\toolbox\Application;
use IPS\toolbox\Profiler\Time;

use function _p;
use function defined;
use function array_sum;
use function array_keys;

use const E_ALL;
use const E_ERROR;
use const E_PARSE;
use const E_NOTICE;
use const E_STRICT;
use const E_WARNING;
use const E_CORE_ERROR;
use const E_USER_ERROR;
use const E_DEPRECATED;
use const E_USER_NOTICE;
use const E_CORE_WARNING;
use const E_USER_WARNING;
use const E_COMPILE_ERROR;
use const E_COMPILE_WARNING;
use const E_USER_DEPRECATED;
use const E_RECOVERABLE_ERROR;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    exit;
}

class toolbox_hook_DevTemplate extends _HOOK_CLASS_
{

    public function __construct($app, $templateLocation, $templateName)
    {
        parent::__construct($app, $templateLocation, $templateName);
        if (
                defined('DT_THEME') &&
                defined('DT_THEME_ID') &&
                DT_THEME === true &&
                DT_THEME_ID !== 0
        ) {
            $this->sourceFolder = \IPS\Application::getRootPath() . '/themes/' . Application::getThemeId() . '/html/' . $app . '/' . $templateLocation . '/' . mb_strtolower($templateName) . '/';
        }
    }

    public function __call($bit, $params)
    {
        if (Settings::i()->dtprofiler_enabled_executions) {
            $time = new Time();
        }
            $parent = parent::__call($bit, $params);

        if (Settings::i()->dtprofiler_enabled_executions) {
            $path = \IPS\Application::getRootPath() . '/applications/' . $this->app . '/dev/html/' . $this->templateLocation . '/' . $this->templateName . '/' . $bit . '.phtml';
            $time->end($path, $path);
        }
        if (Settings::i()->dtprofiler_enabled_templates &&
            !Request::i()->isAjax() &&
            \IPS\QUERY_LOG &&
            $this->app !== 'dtprofiler'
        ) {
            if (isset(Store::i()->dtprofiler_templates)) {
                $log = Store::i()->dtprofiler_templates;
            }

            $log[] = [
                'name'     => $bit,
                'group'    => $this->templateName,
                'location' => $this->templateLocation,
                'app'      => $this->app,
            ];

            Store::i()->dtprofiler_templates = $log;
        }
        return $parent;
    }
}
