//<?php namespace toolbox_IPS_Dispatcher_Standard_aadac28b89813071422b6e80864852e77;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Data\Store;
use IPS\Dispatcher\Admin;
use IPS\Http\Url;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use IPS\toolbox\Application;
use IPS\Widget;

use function defined;

use const DT_NODE;
use const DT_NODE_URL;
use const DT_USE_WSL;
use const DT_WSL_PATH;

if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	exit;
}

abstract class toolbox_hook_dispatcherStandard extends _HOOK_CLASS_
{

    public function run()
    {
        Widget::deleteCaches();
        parent::run();
    }

    protected static function baseJs()
    {
        parent::baseJs();
        Output::i()->jsFiles = array_merge(
            Output::i()->jsFiles,
            Output::i()->js('admin_toggles.js', 'toolbox', 'admin')
        );
        Output::i()->jsVars['cj_debug_key'] = \IPS\SUITE_UNIQUE_KEY;
        Output::i()->jsVars['cj_debug_sockets'] = defined('DT_NODE') && DT_NODE ? 1 : 0;
        Output::i()->jsVars['cj_debug_sockets_url'] = defined('DT_NODE_URL') && DT_NODE ? DT_NODE_URL : '';
        Output::i()->jsVars['cj_debug'] = \IPS\IN_DEV === true || \IPS\DEBUG_JS === true ? 1 : 0;
        Output::i()->jsVars['cj_base_url'] = \IPS\Settings::i()->base_url;
        Output::i()->jsVars['cj_editor'] = \IPS\DEV_WHOOPS_EDITOR;
        Output::i()->jsVars['cj_app_path'] = Application::getRootPath('toolbox');
        Output::i()->jsVars['cj_use_profiler_console'] = Settings::i()->dtprofiler_use_console;
        Output::i()->jsVars['cj_use_profiler_replace_console'] = Settings::i()->dtprofiler_use_console;
        Output::i()->jsVars['cj_wsl_path'] = null;
        Output::i()->jsVars['cj_use_wsl'] = false;
    }

}
