//<?php namespace toolbox_IPS_core_modules_admin_support_phpinfo_ad20f9923dc5f11b54834267a3dcc3c21;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Output;
use IPS\Theme;

use function ob_end_clean;
use function ob_get_clean;
use function ob_get_contents;
use function ob_start;
use function phpinfo;
use function preg_replace;

if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	exit;
}

class toolbox_hook_phpinfo extends _HOOK_CLASS_
{

    protected function manage()
    {
        \IPS\toolbox\Application::addCss(['admin_phpinfo']);
        ob_start();
        phpinfo();
        $content = ob_get_contents();
        try {
            ob_end_clean();
        } catch (Exception $e) {
        }
        $content = preg_replace('#<head>(?:.|\n|\r)+?</head>#miu', '', $content);
        Output::i()->title = 'phpinfo()';
        Output::i()->output = Theme::i()->getTemplate('other', 'toolbox', 'admin')->phpinfo($content);
    }
}
